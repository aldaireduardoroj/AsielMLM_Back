<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProductOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_product_order_id',
        'product_id',
        'product_title',
        'quantity',
        'price',
        'subtotal',
        'points'
    ];
}
