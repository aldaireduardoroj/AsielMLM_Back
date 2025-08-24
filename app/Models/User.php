<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'dni',
        'password',
        'uuid',
        'is_admin',
        'address',
        'phone',
        'photo',
        'city',
        'country',
        'gender'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function file()
    {
        return $this->hasOne(File::class , 'id' , 'photo');
    }

    public function paymentActive()
    {
        return $this->hasOne(PaymentLog::class , 'user_id' , 'id')->where("state", PaymentLog::PAGADO);
    }

    public function range()
    {
        return $this->hasOne(RangeUser::class , 'user_id' , 'id')->where("status", true);
    }

}
