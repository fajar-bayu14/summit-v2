<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\ProcessRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Pesanan;
use App\Models\Refund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RefundController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/refunds',
        summary: 'List all refund requests & disputes (Admin Tier-2)',
        tags: ['Refund (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_disputed', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'refund_category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refund requests fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RefundResource')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Refund::with(['pesanan.user', 'pesanan.basecamp.mitra', 'pembayaran', 'mitra'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('is_disputed') && $request->query('is_disputed') !== null && $request->query('is_disputed') !== '') {
            $query->where('is_disputed', filter_var($request->query('is_disputed'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('refund_category')) {
            $query->where('refund_category', $request->query('refund_category'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('alasan', 'like', "%{$search}%")
                    ->orWhere('dispute_reason', 'like', "%{$search}%")
                    ->orWhereHas('pesanan', function ($pq) use ($search) {
                        $pq->where('invoice', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $refunds = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan refund berhasil dimuat.',
            'data' => RefundResource::collection($refunds)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/refunds/{id}/process',
        summary: 'Process and approve/reject a refund request / dispute (Admin Tier-2)',
        tags: ['Refund (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['success', 'rejected', 'failed'], example: 'success'),
                    new OA\Property(property: 'nominal', type: 'number', nullable: true, example: 50000),
                    new OA\Property(property: 'tipe', type: 'string', enum: ['auto', 'manual'], example: 'manual'),
                    new OA\Property(property: 'bukti_transfer', type: 'string', nullable: true, example: 'proofs/refund_123.jpg'),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Klaim sengketa disetujui sebagian'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund processed successfully'),
            new OA\Response(response: 422, description: 'Validation error / already processed'),
        ]
    )]
    public function process(ProcessRefundRequest $request, int $id): JsonResponse
    {
        $refund = Refund::with('pesanan.basecamp.mitra')->findOrFail($id);

        if (in_array($refund->status, ['success', 'failed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan refund ini sudah selesai diproses sebelumnya.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($refund, $validated) {
            $isSuccess = $validated['status'] === 'success';

            if ($isSuccess) {
                $pesanan = $refund->pesanan;
                $approvedNominal = ! empty($validated['nominal'])
                    ? min((float) $validated['nominal'], (float) $pesanan->total_bayar)
                    : (float) $refund->nominal;

                $refund->update([
                    'status' => 'success',
                    'nominal_disetujui' => $approvedNominal,
                    'tipe' => $validated['tipe'] ?? $refund->tipe,
                    'bukti_transfer' => $validated['bukti_transfer'] ?? $refund->bukti_transfer,
                    'admin_catatan' => $validated['catatan'] ?? null,
                    'refunded_at' => now(),
                ]);

                // Update pesanan status
                if ($approvedNominal >= (float) $pesanan->total_bayar) {
                    $pesanan->update(['status' => 'refunded', 'status_escrow' => 'refunded']);
                }

                // Handle Mitra Wallet Escrow deduction with Negative Balance Recovery
                $mitra = $pesanan->basecamp?->mitra;
                if ($mitra) {
                    $wallet = Wallet::where('mitra_id', $mitra->id)->lockForUpdate()->first();
                    if ($wallet) {
                        $ratio = min(1.0, (float) ($approvedNominal / max(1.0, (float) $pesanan->total_bayar)));
                        $deductAmount = round((float) $pesanan->pendapatan_mitra * $ratio, 2);

                        // If pending balance covers it
                        if ((float) $wallet->saldo_pending >= $deductAmount) {
                            $wallet->saldo_pending = max(0.00, (float) bcsub((string) $wallet->saldo_pending, (string) $deductAmount, 2));
                        } else {
                            // Deduct from pending, remainder from available (can go negative)
                            $pendingPart = (float) $wallet->saldo_pending;
                            $availablePart = (float) bcsub((string) $deductAmount, (string) $pendingPart, 2);

                            $wallet->saldo_pending = 0.00;
                            $wallet->saldo_available = (float) bcsub((string) $wallet->saldo_available, (string) $availablePart, 2);
                        }

                        $wallet->save();

                        WalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'pesanan_id' => $pesanan->id,
                            'type' => 'refund_deduction',
                            'nominal' => $deductAmount,
                            'saldo_pending_after' => $wallet->saldo_pending,
                            'saldo_available_after' => $wallet->saldo_available,
                            'catatan' => 'Pemotongan refund oleh Admin (dispute/resolusi) untuk pesanan: '.$pesanan->invoice,
                        ]);
                    }
                }
            } else {
                $status = $validated['status'] === 'rejected' ? 'rejected' : 'failed';
                $refund->update([
                    'status' => $status,
                    'admin_catatan' => $validated['catatan'] ?? null,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Proses refund berhasil diperbarui oleh Admin.',
            'data' => new RefundResource($refund->fresh(['pesanan', 'mitra'])),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/refunds/force-majeure',
        summary: 'Mass cancel and refund orders due to force majeure trail closure (Admin)',
        tags: ['Refund (Admin)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jalur_id', 'alasan'],
                properties: [
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-09-05'),
                    new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-09-07'),
                    new OA\Property(property: 'alasan', type: 'string', example: 'Penutupan jalur resmi Balai Taman Nasional karena badai'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Force majeure bulk refund executed'),
        ]
    )]
    public function forceMajeure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jalur_id' => ['required', 'integer', 'exists:jalur_pendakians,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $startDate = $validated['tanggal_mulai'] ?? $validated['start_date'] ?? null;
        $endDate = $validated['tanggal_selesai'] ?? $validated['end_date'] ?? null;

        $query = Pesanan::where('jalur_id', $validated['jalur_id'])
            ->whereIn('status', ['paid', 'on_going'])
            ->with(['basecamp.mitra', 'pembayaran']);

        if (! empty($startDate)) {
            $query->whereDate('tanggal_booking', '>=', $startDate);
        }
        if (! empty($endDate)) {
            $query->whereDate('tanggal_booking', '<=', $endDate);
        }

        $orders = $query->get();
        $processedCount = 0;

        DB::transaction(function () use ($orders, $validated, &$processedCount) {
            foreach ($orders as $pesanan) {
                if (! $pesanan->pembayaran) {
                    continue;
                }

                $refund = Refund::updateOrCreate(
                    ['pesanan_id' => $pesanan->id],
                    [
                        'pembayaran_id' => $pesanan->pembayaran->id,
                        'mitra_id' => $pesanan->basecamp?->mitra_id,
                        'refund_category' => 'force_majeure',
                        'nominal' => $pesanan->total_bayar,
                        'nominal_disetujui' => $pesanan->total_bayar,
                        'alasan' => $validated['alasan'],
                        'status' => 'success',
                        'tipe' => 'manual',
                        'admin_catatan' => 'Otomasi Force Majeure Penutupan Jalur',
                        'refunded_at' => now(),
                    ]
                );

                $pesanan->update(['status' => 'refunded', 'status_escrow' => 'refunded']);

                $mitra = $pesanan->basecamp?->mitra;
                if ($mitra) {
                    $wallet = Wallet::where('mitra_id', $mitra->id)->lockForUpdate()->first();
                    if ($wallet) {
                        $deductAmount = (float) $pesanan->pendapatan_mitra;
                        $wallet->saldo_pending = max(0.00, (float) bcsub((string) $wallet->saldo_pending, (string) $deductAmount, 2));
                        $wallet->save();

                        WalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'pesanan_id' => $pesanan->id,
                            'type' => 'refund_deduction',
                            'nominal' => $deductAmount,
                            'saldo_pending_after' => $wallet->saldo_pending,
                            'saldo_available_after' => $wallet->saldo_available,
                            'catatan' => 'Pemotongan Force Majeure Penutupan Jalur: '.$pesanan->invoice,
                        ]);
                    }
                }

                $processedCount++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil memproses force majeure refund untuk {$processedCount} pesanan.",
            'data' => [
                'total_pesanan_refunded' => $processedCount,
                'total_refunded' => $processedCount,
            ],
        ]);
    }
}
