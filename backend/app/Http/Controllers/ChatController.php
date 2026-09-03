<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Http\Requests\Chat\StoreChatRoomRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ChatController extends Controller
{
    #[OA\Post(
        path: '/api/v1/chat/rooms',
        summary: 'Get or create chat room for an order',
        tags: ['In-App Chat'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pesanan_id'],
                properties: [
                    new OA\Property(property: 'pesanan_id', type: 'integer', example: 10),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chat room fetched or created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ChatRoomResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden / unauthorized participant'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function createOrGetRoom(StoreChatRoomRequest $request): JsonResponse
    {
        $pesananId = $request->validated()['pesanan_id'];
        $pesanan = Pesanan::with(['basecamp.mitra.user', 'user'])->findOrFail($pesananId);

        $currentUser = $request->user();
        $pendakiId = $pesanan->user_id;
        $mitraUserId = $pesanan->basecamp?->mitra?->user_id;

        if (! in_array($currentUser->id, [$pendakiId, $mitraUserId], true) && $currentUser->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan partisipan yang berhak mengakses ruang obrolan pesanan ini.',
            ], 403);
        }

        $room = ChatRoom::firstOrCreate(
            ['pesanan_id' => $pesanan->id],
            [
                'pendaki_user_id' => $pendakiId,
                'mitra_user_id' => $mitraUserId ?? $currentUser->id,
                'status' => 'active',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Ruang obrolan berhasil dibuka.',
            'data' => new ChatRoomResource($room->load(['pesanan.jalur.gunung', 'pendaki', 'mitra'])),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/chat/rooms',
        summary: 'List user active chat rooms',
        tags: ['In-App Chat'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chat rooms listed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ChatRoomResource')),
                    ]
                )
            ),
        ]
    )]
    public function rooms(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rooms = ChatRoom::where('pendaki_user_id', $userId)
            ->orWhere('mitra_user_id', $userId)
            ->with(['pesanan.jalur.gunung', 'pendaki', 'mitra'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar percakapan berhasil dimuat.',
            'data' => ChatRoomResource::collection($rooms)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/chat/rooms/{room_id}/messages',
        summary: 'Get message history of a chat room',
        tags: ['In-App Chat'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'room_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Messages history fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ChatMessageResource')),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Chat room not found'),
        ]
    )]
    public function messages(Request $request, int $roomId): JsonResponse
    {
        $room = ChatRoom::findOrFail($roomId);
        $userId = $request->user()->id;

        if (! in_array($userId, [$room->pendaki_user_id, $room->mitra_user_id], true) && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin mengakses pesan pada ruang ini.',
            ], 403);
        }

        $messages = $room->messages()->with('sender')->latest()->paginate(30);

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat pesan berhasil dimuat.',
            'data' => ChatMessageResource::collection($messages)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/chat/rooms/{room_id}/messages',
        summary: 'Send text message or image attachment to chat room',
        tags: ['In-App Chat'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'room_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'attachment', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Message sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ChatMessageResource'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Chat room is closed'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function sendMessage(StoreChatMessageRequest $request, int $roomId): JsonResponse
    {
        $room = ChatRoom::findOrFail($roomId);
        $userId = $request->user()->id;

        if (! in_array($userId, [$room->pendaki_user_id, $room->mitra_user_id], true) && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin mengirim pesan pada ruang ini.',
            ], 403);
        }

        if ($room->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Ruang obrolan ini telah ditutup.',
                'error_code' => 'ERR_CHAT_ROOM_CLOSED',
            ], 400);
        }

        $validated = $request->validated();
        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $attachmentUrl = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_id' => $userId,
            'message' => $validated['message'] ?? null,
            'attachment_url' => $attachmentUrl,
            'is_read' => false,
        ]);

        $room->update(['last_message_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dikirim.',
            'data' => new ChatMessageResource($message->load('sender')),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/chat/rooms/{room_id}/read',
        summary: 'Mark all unread messages in chat room as read',
        tags: ['In-App Chat'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'room_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Messages marked as read'),
        ]
    )]
    public function markAsRead(Request $request, int $roomId): JsonResponse
    {
        $room = ChatRoom::findOrFail($roomId);
        $userId = $request->user()->id;

        $room->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan ditandai sebagai dibaca.',
        ]);
    }
}
