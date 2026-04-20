<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KoiImage extends Model
{
    protected $fillable = ['koi_id', 'image_path', 'sort_order'];

    protected $appends = ['url'];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? asset('storage/' . $this->image_path) : null,
        );
    }

    public function koi(): BelongsTo
    {
        return $this->belongsTo(Koi::class);
    }
}
