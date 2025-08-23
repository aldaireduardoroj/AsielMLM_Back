<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrderPoint extends Model
{
    use HasFactory;

    const PATROCINIO = "P";
    const RESIDUAL = "R";
    const GRUPAL = "G";

    const COMPRA = "B";
    const RESET = "S";
    const INFINITO = "I";

    const AFILIADOS = "A";

    protected $fillable = [
        'payment_order_id',
        'user_code',
        'sponsor_code',
        'point',
        'payment',
        'type',
        'state',
        'user_id'
    ];

    protected $hidden = [
        'created_user_id',
        'updated_user_id'
    ];

    public function paymentOrder()
    {
        return $this->hasOne(PaymentOrder::class , 'id' , 'payment_order_id');
    }

    public function sponsor()
    {
        return $this->hasOne(User::class , 'uuid' , 'sponsor_code');
    }

    public function patrocinador()
    {
        return $this->hasOne(User::class , 'uuid' , 'sponsor_code');
    }

    public function user()
    {
        return $this->hasOne(User::class , 'uuid' , 'user_code');
    }

    public function userPoint()
    {
        return $this->hasOne(User::class , 'id' , 'user_id');
    }
}
