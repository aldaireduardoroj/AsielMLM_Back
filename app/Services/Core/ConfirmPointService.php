<?php

namespace App\Services\Core;

use App\Models\PaymentOrderPoint;
use App\Models\User;

class ConfirmPointService
{
    const MAX_CHILD = 3;

    public function maxChilds($userCode)
    {
        $user = User::where("uuid", $userCode)->first();
        if( $user->is_admin ) return false;

        $paymentOrderPointCount = PaymentOrderPoint::select('user_code', 'sponsor_code')
            ->distinct()
            ->where("sponsor_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::PATROCINIO ])
            ->where("payment" , true)
            ->count();
        
        return $paymentOrderPointCount >= self::MAX_CHILD;
    }
}