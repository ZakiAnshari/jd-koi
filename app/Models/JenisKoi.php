<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisKoi extends Model
{
    protected $fillable = [
        'nama_jenis',
        'deskripsi',
    ];
}
