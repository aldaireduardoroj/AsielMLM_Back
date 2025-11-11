<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackProductPercentage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pack_id',
        'product_id',
        'discount',
    ];
}