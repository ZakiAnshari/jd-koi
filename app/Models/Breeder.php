<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Breeder extends Model
{
    use LogsActivity;

    protected $logModule = 'Breeder';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'name_breeder',
    ];

    protected function getActivityItemName(): ?string
    {
        return $this->name_breeder;
    }
}
