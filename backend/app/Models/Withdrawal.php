<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'mitra_id',
        'wallet_id',
        'disbursement_id',
        'nominal',
        'biaya_admin',
        'bank',
        'rekening_bank',
        'nama_rekening',
        'status',
        'catatan',
        'alasan_penolakan',
        'failure_reason',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'biaya_admin' => 'decimal:2',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
