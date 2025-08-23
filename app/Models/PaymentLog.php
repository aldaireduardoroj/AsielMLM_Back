<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class PaymentLog extends Model
{
    use HasFactory, Uuid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'id';

    const PENDIENTEPAGO = 1;
    const PAGADO = 2;
    const RECHAZADO = 3;
    const ANULADO = 4;
    const ERROR = 5;
    const TERMINADO = 6;
    const RESET = 7;
    const REBBOT = 8;
    const PREORDER = 9;
    /*
        1 PENDIENTE DE PAGO
        2 PAGADA
        3 RECHAZADA
        4 ANULADA
        5 ERROR
        6 TERMINADO
    */

    protected $fillable = [
        'payment_order_id',
        'user_id',
        'state',
        'message',
        'log',
        'confirm'
    ];

    protected $hidden = [
        'user_id',
        'created_user_id',
        'updated_user_id'
    ];

    public function paymentOrder()
    {
        return $this->hasOne(PaymentOrder::class , 'id' , 'payment_order_id');
    }
}
