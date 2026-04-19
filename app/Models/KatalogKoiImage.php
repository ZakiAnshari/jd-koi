<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KatalogKoiImage extends Model
{
    protected $fillable = ['katalog_koi_id', 'image_path', 'sort_order'];

    public function katalogKoi(): BelongsTo
    {
        return $this->belongsTo(KatalogKoi::class);
    }
}
