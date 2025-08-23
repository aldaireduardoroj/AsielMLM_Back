<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class PaymentOrder extends Model
{
    use HasFactory, Uuid;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'currency',
        'amount',
        'sponsor_code',
        'pack_id',
        'token',
    ];

    protected $hidden = [
        'created_user_id',
        'updated_user_id'
    ];

    public function paymentLog()
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function pack()
    {
        return $this->hasOne(Pack::class, 'id' ,'pack_id');
    }

    public function sponsor()
    {
        return $this->hasOne(User::class, 'uuid' ,'sponsor_code');
    }
}
