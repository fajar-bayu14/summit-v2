<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gunung;
use App\Models\Mitra;
use App\Models\Pendaki;
use App\Models\Refund;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AnalyticsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/analytics/summary',
        summary: 'Get actionable pending queue counters and system metrics for Admin Central notifications',
        tags: ['Analytics (Admin)'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin summary metrics and action queues fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Ringkasan antrean tugas admin berhasil dimuat.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'action_queues',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'kyc_pending_count', type: 'integer', example: 3),
                                        new OA\Property(property: 'withdrawal_pending_count', type: 'integer', example: 1),
                                        new OA\Property(property: 'refund_pending_count', type: 'integer', example: 2),
                                    ]
                                ),
                                new OA\Property(property: 'total_pending', type: 'integer', example: 6),
                                new OA\Property(
                                    property: 'overview',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_gunung', type: 'integer', example: 10),
                                        new OA\Property(property: 'total_mitra', type: 'integer', example: 15),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - only for admin'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $kycPendingCount = Pendaki::where('status_verifikasi', 'pending')->count();
        $withdrawalPendingCount = Withdrawal::where('status', 'pending')->count();
        $refundPendingCount = Refund::where('status', 'pending')->count();

        $totalPending = $kycPendingCount + $withdrawalPendingCount + $refundPendingCount;

        return response()->json([
            'status' => 'success',
            'message' => 'Ringkasan antrean tugas admin berhasil dimuat.',
            'data' => [
                'action_queues' => [
                    'kyc_pending_count' => $kycPendingCount,
                    'withdrawal_pending_count' => $withdrawalPendingCount,
                    'refund_pending_count' => $refundPendingCount,
                ],
                'total_pending' => $totalPending,
                'overview' => [
                    'total_gunung' => Gunung::count(),
                    'total_mitra' => Mitra::count(),
                ],
            ],
        ]);
    }
}
