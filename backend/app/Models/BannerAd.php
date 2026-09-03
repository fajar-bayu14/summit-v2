<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerAd extends Model
{
    use HasFactory;

    protected $fillable = [
        'mitra_id',
        'judul',
        'gambar',
        'link_url',
        'posisi',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_impressions',
        'total_clicks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'total_impressions' => 'integer',
            'total_clicks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }
}
