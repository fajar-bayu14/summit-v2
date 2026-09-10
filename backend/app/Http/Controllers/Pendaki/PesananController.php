<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pesanan\CheckoutRequest;
use App\Http\Requests\Pesanan\CreatePesananRequest;
use App\Http\Resources\PesananResource;
use App\Models\Cart;
use App\Models\Pesanan;
use App\Services\CartService;
use App\Services\PesananService;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Xendit\XenditSdkException;

class PesananController extends Controller
{
    /**
     * Inject the required services.
     */
    public function __construct(
        protected PesananService $pesananService,
        protected CartService $cartService,
        protected XenditService $xenditService
    ) {}

    #[OA\Post(
        path: '/api/v1/orders',
        summary: 'Create a new climber booking order (Pesanan)',
        tags: ['Pesanan (Climber)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basecamp_id', 'jalur_id', 'tanggal_booking', 'anggotas', 'items'],
                properties: [
                    new OA\Property(property: 'basecamp_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal_booking', type: 'string', format: 'date', example: '2026-07-10'),
                    new OA\Property(
                        property: 'anggotas',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'nama_anggota', type: 'string', example: 'Jane Doe'),
                                new OA\Property(property: 'nik_identitas', type: 'string', example: '1234567890123456'),
                                new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
                                new OA\Property(property: 'telepon_darurat', type: 'string', example: '081298765432'),
                                new OA\Property(property: 'hubungan_darurat', type: 'string', example: 'Istri'),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'produk_id', type: 'integer', example: 1),
                                new OA\Property(property: 'qty', type: 'integer', example: 2),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Order created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / Out of stock or quota'),
        ]
    )]
    public function store(CreatePesananRequest $request): JsonResponse
    {
        try {
            $pesanan = $this->pesananService->createPesanan(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully.',
                'data' => new PesananResource($pesanan),
            ], 201);
        } catch (LockTimeoutException $e) {
            throw ValidationException::withMessages([
                'transaction' => ['Proses pemesanan sedang berlangsung, silakan tunggu beberapa saat.'],
            ]);
        }
    }

    #[OA\Get(
        path: '/api/v1/orders',
        summary: 'List the climber booking orders (paginated)',
        tags: ['Pesanan (Climber)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'pending')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of orders',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesananResource')),
                        new OA\Property(property: 'meta', properties: [
                            new OA\Property(property: 'current_page', type: 'integer', example: 1),
                            new OA\Property(property: 'last_page', type: 'integer', example: 1),
                            new OA\Property(property: 'per_page', type: 'integer', example: 15),
                            new OA\Property(property: 'total', type: 'integer', example: 1),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Pesanan::with(['basecamp', 'jalur', 'pembayaran', 'refunds'])
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $pesanans = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => PesananResource::collection($pesanans),
            'meta' => [
                'current_page' => $pesanans->currentPage(),
                'last_page' => $pesanans->lastPage(),
                'per_page' => $pesanans->perPage(),
                'total' => $pesanans->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/orders/{invoice}',
        summary: 'Get a single booking order by invoice number',
        tags: ['Pesanan (Climber)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(Request $request, string $invoice): JsonResponse
    {
        $pesanan = Pesanan::where('invoice', $invoice)
            ->with(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran', 'refunds'])
            ->firstOrFail();

        $this->authorize('view', $pesanan);

        return response()->json([
            'status' => 'success',
            'data' => new PesananResource($pesanan),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/orders/checkout',
        summary: 'Checkout the active cart into a pending order and generate a Xendit invoice',
        tags: ['Pesanan (Climber)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['anggotas'],
                properties: [
                    new OA\Property(
                        property: 'anggotas',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'nama_anggota', type: 'string', example: 'Jane Doe'),
                                new OA\Property(property: 'nik_identitas', type: 'string', example: '1234567890123456'),
                                new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created and payment link generated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Order created, silakan lanjutkan pembayaran.'),
                        new OA\Property(property: 'checkout_url', type: 'string', example: 'https://checkout.xendit.co/...'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / Empty cart or insufficient stock'),
        ]
    )]
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('items')
            ->latest()
            ->first();

        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => ['Keranjang belanja masih kosong. Tambahkan item terlebih dahulu.'],
            ]);
        }

        try {
            $pesanan = $this->pesananService->checkout($cart, $request->validated()['anggotas']);
        } catch (LockTimeoutException $e) {
            throw ValidationException::withMessages([
                'transaction' => ['Proses pemesanan sedang berlangsung, silakan tunggu beberapa saat.'],
            ]);
        }

        $pembayaran = $pesanan->pembayaran;
        $checkoutUrl = null;
        $message = 'Order created successfully.';

        try {
            $invoice = $this->xenditService->createInvoice($pesanan, $pembayaran);

            $pembayaran->update([
                'provider' => 'Xendit',
                'reference_id' => $invoice->getId(),
                'checkout_url' => $invoice->getInvoiceUrl(),
                'expired_at' => Carbon::parse($invoice->getExpiryDate()),
            ]);

            $checkoutUrl = $invoice->getInvoiceUrl();
            $message = 'Order created, silakan lanjutkan pembayaran.';
        } catch (XenditSdkException $e) {
            report($e);

            $message = 'Order berhasil dibuat, tetapi pembuatan tautan pembayaran gagal. Silakan coba lagi.';
        }

        $pesanan->load(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran']);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'checkout_url' => $checkoutUrl,
            'data' => new PesananResource($pesanan),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/orders/{invoice}/cancel',
        summary: 'Cancel a pending booking order',
        tags: ['Pesanan (Climber)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Order is not pending'),
        ]
    )]
    public function cancel(Request $request, string $invoice): JsonResponse
    {
        $pesanan = Pesanan::where('invoice', $invoice)
            ->with(['pembayaran', 'details.produk', 'details.produk.tiket'])
            ->firstOrFail();

        $this->authorize('cancel', $pesanan);

        if (! $this->pesananService->cancel($pesanan)) {
            throw ValidationException::withMessages([
                'order' => ['Pesanan tidak dapat dibatalkan karena statusnya bukan pending.'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => new PesananResource($pesanan),
        ]);
    }
}
