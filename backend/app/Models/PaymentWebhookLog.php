<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pesanan_id',
    'pembayaran_id',
    'provider',
    'event',
    'external_id',
    'status_raw',
    'payload',
    'ip_address',
    'is_valid',
    'error_message',
])]
class PaymentWebhookLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_webhook_logs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_valid' => 'boolean',
        ];
    }

    /**
     * Get the order this webhook belongs to, if any.
     *
     * @return BelongsTo<Pesanan, PaymentWebhookLog>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Get the payment this webhook belongs to, if any.
     *
     * @return BelongsTo<Pembayaran, PaymentWebhookLog>
     */
    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }
}
