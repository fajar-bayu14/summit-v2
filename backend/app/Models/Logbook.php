<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'pesanan_id',
        'user_id',
        'foto_summit',
        'latitude',
        'longitude',
        'waktu_summit',
        'catatan_pendaki',
        'status_validasi',
        'catatan_petugas',
        'validated_at',
        'validated_by',
        'certificate_path',
    ];

    protected function casts(): array
    {
        return [
            'waktu_summit' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
