<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use LogsActivity;

    protected $logModule = 'Customer';
    protected $logExcept = ['updated_at'];

    protected $fillable = [
        'name',
        'phone',
        'address',
    ];

    protected function getActivityItemName(): ?string
    {
        return $this->name;
    }
}
