<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'pesanan_id',
        'type',
        'nominal',
        'saldo_pending_after',
        'saldo_available_after',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'saldo_pending_after' => 'decimal:2',
            'saldo_available_after' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }
}
