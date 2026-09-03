<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraStaff extends Model
{
    use HasFactory;

    protected $table = 'mitra_staff';

    protected $fillable = [
        'mitra_id',
        'basecamp_id',
        'nama',
        'role',
        'telepon',
        'is_available',
        'jadwal_tugas',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function basecamp(): BelongsTo
    {
        return $this->belongsTo(Basecamp::class);
    }
}
