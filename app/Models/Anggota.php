<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anggota extends Model
{
    protected $fillable = [
        'nama',
        'no_hp',
        'alamat',
    ];

    public function blokLahans(): HasMany
    {
        return $this->hasMany(BlokLahan::class, 'anggota_id');
    }
}
