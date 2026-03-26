<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Koi extends Model
{
    protected $fillable = [
        'breeder_id',
        'code',
        'name',
        'photo',
    ];

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }
}
