<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KoiImage extends Model
{
    protected $fillable = ['koi_id', 'image_path', 'sort_order'];

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
