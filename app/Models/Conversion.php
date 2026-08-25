<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'input_datetime',
        'epoch_timestamp',
    ];

    protected $casts = [
        'input_datetime' => 'datetime',
        'epoch_timestamp' => 'integer',
    ];
}