<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Koi;

class StockMovement extends Model
{
    protected $fillable = [
        'koi_id',
        'type',
        'qty',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
