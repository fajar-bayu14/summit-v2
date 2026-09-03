<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ChatMessageResource',
    title: 'Chat Message Resource',
    description: 'Representation of a single text/image message in a chat room',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'chat_room_id', type: 'integer', example: 1),
        new OA\Property(property: 'sender_id', type: 'integer', example: 5),
        new OA\Property(property: 'sender_name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'message', type: 'string', nullable: true, example: 'Halo pos Cibodas, apakah tenda dome sudah siap?'),
        new OA\Property(property: 'attachment_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/chat_attachments/photo.jpg'),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'is_mine', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'chat_room_id' => $this->chat_room_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'message' => $this->message,
            'attachment_url' => $this->attachment_url ? asset('storage/'.$this->attachment_url) : null,
            'is_read' => (bool) $this->is_read,
            'is_mine' => $userId ? $this->sender_id === $userId : false,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
