<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratonialResidualPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'range_id',
        'point_id',
        'points',
        'level',
        'state',
    ];
}
