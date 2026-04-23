<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Koi extends Model
{
    use LogsActivity;

    protected $logModule = 'Data Koi';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'breeder_id',
        'jenis_koi_id',
        'code',
        'name',
        'photo',
        'age',
        'size',
        'box_qty',
        'weight',
        'total_weight',
    ];

    protected $casts = [
        'age'          => 'integer',
        'size'         => 'decimal:2',
        'box_qty'      => 'integer',
        'weight'       => 'decimal:3',
        'total_weight' => 'decimal:3',
    ];

    protected function getActivityItemName(): ?string
    {
        return "{$this->code} - {$this->name}";
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }

    public function jenisKoi(): BelongsTo
    {
        return $this->belongsTo(JenisKoi::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(KoiImage::class)->orderBy('sort_order');
    }
}
