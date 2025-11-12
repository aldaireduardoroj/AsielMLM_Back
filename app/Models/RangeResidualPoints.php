<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RangeResidualPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'range_id',
        'level1',
        'level2',
        'level3',
        'level4',
        'level5',
        'level6',
        'level7',
    ];
}
