<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Koi extends Model
{
    use LogsActivity;

    protected $logModule = 'Data Koi';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'breeder_id',
        'code',
        'name',
        'photo',
    ];

    protected function getActivityItemName(): ?string
    {
        return "{$this->code} - {$this->name}";
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(Breeder::class);
    }
}
