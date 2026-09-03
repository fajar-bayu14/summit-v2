<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\CreateCartRequest;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    /**
     * Inject CartService.
     */
    public function __construct(
        protected CartService $cartService
    ) {}

    #[OA\Get(
        path: '/api/v1/cart',
        summary: 'Get the climber active shopping cart',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Active cart retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['items.produk', 'basecamp', 'jalur'])
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $cart ? new CartResource($cart) : null,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/cart',
        summary: 'Create or update the booking context of the active cart',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basecamp_id', 'jalur_id', 'tanggal_booking'],
                properties: [
                    new OA\Property(property: 'basecamp_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal_booking', type: 'string', format: 'date', example: '2026-08-10'),
                    new OA\Property(property: 'tanggal_selesai_booking', type: 'string', format: 'date', example: '2026-08-12'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cart created or updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(CreateCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateActiveCart(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Keranjang berhasil diperbarui.',
            'data' => new CartResource($cart->load(['items.produk', 'basecamp', 'jalur'])),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/cart/items',
        summary: 'Add a product to the active cart',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['produk_id', 'qty', 'jalur_id', 'tanggal_booking'],
                properties: [
                    new OA\Property(property: 'produk_id', type: 'integer', example: 1),
                    new OA\Property(property: 'qty', type: 'integer', example: 2),
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal_booking', type: 'string', format: 'date', example: '2026-08-10'),
                    new OA\Property(property: 'tanggal_selesai_booking', type: 'string', format: 'date', example: '2026-08-12'),
                    new OA\Property(property: 'tanggal_mulai_sewa', type: 'string', format: 'date', example: '2026-08-10'),
                    new OA\Property(property: 'tanggal_selesai_sewa', type: 'string', format: 'date', example: '2026-08-12'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Item added to cart',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / Insufficient stock'),
        ]
    )]
    public function addItem(StoreCartItemRequest $request): JsonResponse
    {
        $this->cartService->addItem($request->user(), $request->validated());

        $cart = Cart::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['items.produk', 'basecamp', 'jalur'])
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Item berhasil ditambahkan ke keranjang.',
            'data' => new CartResource($cart),
        ], 201);
    }

    #[OA\Patch(
        path: '/api/v1/cart/items/{itemId}',
        summary: 'Update quantity or rental details of a cart item',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'qty', type: 'integer', example: 3),
                    new OA\Property(property: 'tanggal_mulai_sewa', type: 'string', format: 'date', example: '2026-08-10'),
                    new OA\Property(property: 'tanggal_selesai_sewa', type: 'string', format: 'date', example: '2026-08-12'),
                    new OA\Property(property: 'catatan_item', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cart item updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Item not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $this->cartService->updateItem($request->user(), $itemId, $request->validated());

        $cart = Cart::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['items.produk', 'basecamp', 'jalur'])
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Item keranjang berhasil diperbarui.',
            'data' => new CartResource($cart),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/cart/items/{itemId}',
        summary: 'Remove an item from the active cart',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cart item removed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroyItem(Request $request, int $itemId): JsonResponse
    {
        $this->cartService->removeItem($request->user(), $itemId);

        return response()->json([
            'status' => 'success',
            'message' => 'Item berhasil dihapus dari keranjang.',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/cart',
        summary: 'Clear the active shopping cart',
        tags: ['Cart (Climber)'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Cart cleared'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Keranjang berhasil dikosongkan.',
        ]);
    }
}
