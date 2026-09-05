<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\JalurPendakian;
use App\Models\KuotaHarianTiket;
use App\Models\Logbook;
use App\Models\MitraStaff;
use App\Models\Pesanan;
use App\Models\PesananAnggota;
use App\Models\Produk;
use App\Models\Refund;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/analytics/summary',
        summary: 'Get dashboard KPI summary metrics, queues and operational status (Mitra)',
        tags: ['Analytics (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'basecamp_id', in: 'query', description: 'Filter metrics by specific basecamp ID', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Analytics summary metrics fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Ringkasan metrik analitik mitra berhasil dimuat.'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'financial', type: 'object'),
                            new OA\Property(property: 'operations_today', type: 'object'),
                            new OA\Property(property: 'action_queues', type: 'object'),
                            new OA\Property(property: 'resources', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function summary(Request $request, EscrowService $escrowService): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $allBasecampIds = $mitra->basecamps->pluck('id');

        // Apply optional basecamp filter
        if ($request->filled('basecamp_id')) {
            $filteredId = (int) $request->query('basecamp_id');
            if ($allBasecampIds->contains($filteredId)) {
                $basecampIds = collect([$filteredId]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk basecamp ini.',
                ], 403);
            }
        } else {
            $basecampIds = $allBasecampIds;
        }

        $today = now()->toDateString();
        $jalurIds = $mitra->basecamps()->whereIn('id', $basecampIds)->pluck('jalur_id')->unique();

        // 1. Financial Metrics
        $wallet = $escrowService->getOrCreateWalletWithLock($mitra);
        $paidRevenueStats = Pesanan::whereIn('basecamp_id', $basecampIds)
            ->whereIn('status', ['paid', 'on_going', 'completed'])
            ->selectRaw('SUM(pendapatan_mitra) as total_revenue, COUNT(id) as paid_count')
            ->first();

        $totalPenarikan = (float) $mitra->withdrawals()
            ->where('status', 'completed')
            ->sum('nominal');

        $financial = [
            'saldo_available' => (float) $wallet->saldo_available,
            'saldo_pending' => (float) $wallet->saldo_pending,
            'total_penarikan' => $totalPenarikan,
            'total_pendapatan_bersih' => (float) ($paidRevenueStats->total_revenue ?? 0.00),
            'total_transaksi_paid' => (int) ($paidRevenueStats->paid_count ?? 0),
        ];

        // 2. Operations Today Metrics
        $pendakiHariIni = PesananAnggota::whereHas('pesanan', function ($q) use ($basecampIds, $today) {
            $q->whereIn('basecamp_id', $basecampIds)
                ->whereDate('tanggal_booking', $today)
                ->whereIn('status', ['paid', 'on_going', 'completed']);
        })->count();

        $pendakiSedangMendaki = PesananAnggota::whereHas('pesanan', function ($q) use ($basecampIds) {
            $q->whereIn('basecamp_id', $basecampIds)
                ->where('status', 'on_going');
        })->count();

        $kuotaHarian = KuotaHarianTiket::whereHas('tiket.produk', function ($q) use ($basecampIds) {
            $q->whereIn('basecamp_id', $basecampIds);
        })->whereDate('tanggal', $today)->first();

        $isJalurClosed = JalurPendakian::whereIn('id', $jalurIds)->where('status', 'close')->exists();

        $operationsToday = [
            'tanggal' => $today,
            'pendaki_berangkat_hari_ini' => $pendakiHariIni,
            'pendaki_sedang_mendaki' => $pendakiSedangMendaki,
            'total_kuota_hari_ini' => $kuotaHarian ? (int) $kuotaHarian->kuota_total : 0,
            'kuota_terpakai_hari_ini' => $kuotaHarian ? (int) ($kuotaHarian->kuota_total - $kuotaHarian->kuota_tersisa) : 0,
            'sisa_kuota_hari_ini' => $kuotaHarian ? (int) $kuotaHarian->kuota_tersisa : 0,
            'status_jalur' => $isJalurClosed ? 'close' : 'open',
        ];

        // 3. Actionable Queues
        $pesananPaidCount = Pesanan::whereIn('basecamp_id', $basecampIds)
            ->where('status', 'paid')
            ->count();

        $logbookPendingCount = Logbook::whereHas('pesanan', function ($q) use ($basecampIds) {
            $q->whereIn('basecamp_id', $basecampIds);
        })->where('status_validasi', 'pending')->count();

        $refundPendingCount = Refund::where('mitra_id', $mitra->id)
            ->where('status', 'pending')
            ->count();

        $actionQueues = [
            'pesanan_paid_count' => $pesananPaidCount,
            'logbook_pending_count' => $logbookPendingCount,
            'refund_pending_count' => $refundPendingCount,
        ];

        // 4. Resources & Inventory
        $totalProdukAktif = Produk::whereIn('basecamp_id', $basecampIds)
            ->where('is_active', true)
            ->count();

        $totalStafTersedia = MitraStaff::where('mitra_id', $mitra->id)
            ->when($request->filled('basecamp_id'), fn ($q) => $q->where('basecamp_id', $request->query('basecamp_id')))
            ->where('is_available', true)
            ->count();

        $totalStafBertugas = MitraStaff::where('mitra_id', $mitra->id)
            ->when($request->filled('basecamp_id'), fn ($q) => $q->where('basecamp_id', $request->query('basecamp_id')))
            ->where('is_available', false)
            ->count();

        $produkStokMenipisCount = Produk::whereIn('basecamp_id', $basecampIds)
            ->where('kategori', 'rental')
            ->where('stok', '<', 3)
            ->count();

        $resources = [
            'total_basecamp' => $basecampIds->count(),
            'total_produk_aktif' => $totalProdukAktif,
            'total_staf_tersedia' => $totalStafTersedia,
            'total_staf_bertugas' => $totalStafBertugas,
            'produk_stok_menipis_count' => $produkStokMenipisCount,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Ringkasan metrik analitik mitra berhasil dimuat.',
            'data' => [
                'financial' => $financial,
                'operations_today' => $operationsToday,
                'action_queues' => $actionQueues,
                'resources' => $resources,
            ],
        ]);
    }
}
