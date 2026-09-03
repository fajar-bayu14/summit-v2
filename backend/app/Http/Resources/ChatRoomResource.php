<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ChatRoomResource',
    title: 'Chat Room Resource',
    description: 'Representation of an active communication channel for an order',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pesanan_id', type: 'integer', example: 10),
        new OA\Property(property: 'invoice', type: 'string', example: 'INV/20260903/ABC123'),
        new OA\Property(property: 'pendaki_nama', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'mitra_nama', type: 'string', example: 'Basecamp Cibodas'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'closed'], example: 'active'),
        new OA\Property(property: 'unread_count', type: 'integer', example: 2),
        new OA\Property(property: 'last_message', ref: '#/components/schemas/ChatMessageResource', nullable: true),
    ]
)]
class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $unreadCount = $userId
            ? $this->messages()->where('sender_id', '!=', $userId)->where('is_read', false)->count()
            : 0;

        $lastMessage = $this->messages()->latest()->first();

        return [
            'id' => $this->id,
            'pesanan_id' => $this->pesanan_id,
            'invoice' => $this->pesanan?->invoice,
            'gunung_nama' => $this->pesanan?->jalur?->gunung?->nama_gunung,
            'pendaki' => [
                'id' => $this->pendaki_user_id,
                'name' => $this->pendaki?->name,
                'avatar' => $this->pendaki?->avatar ? asset('storage/'.$this->pendaki->avatar) : null,
            ],
            'mitra' => [
                'id' => $this->mitra_user_id,
                'name' => $this->mitra?->name,
                'basecamp_nama' => $this->pesanan?->basecamp?->nama_basecamp,
            ],
            'status' => $this->status,
            'unread_count' => $unreadCount,
            'last_message' => $lastMessage ? new ChatMessageResource($lastMessage) : null,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
