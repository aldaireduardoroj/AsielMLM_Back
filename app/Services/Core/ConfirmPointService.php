<?php

namespace App\Services\Core;

use App\Models\PaymentOrderPoint;
use App\Models\User;

class ConfirmPointService
{
    const MAX_CHILD = 3;
    const MAX_CHILD_ADMIN = 15;

    public function maxChilds($userCode)
    {
        $maxChild = self::MAX_CHILD;
        $user = User::where("uuid", $userCode)->first();
        if( $user->is_admin ) $maxChild = self::MAX_CHILD_ADMIN;

        $paymentOrderPointCount = PaymentOrderPoint::select('user_code', 'sponsor_code')
            ->distinct()
            ->where("sponsor_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::PATROCINIO ])
            ->where("payment" , true)
            ->count();
        
        return $paymentOrderPointCount >= $maxChild;
    }

    public function verifyChildNewSponsor($userCode)
    {
        
        if( !$this->maxChilds($userCode) ) return $userCode;

        $paymentOrderPoints = PaymentOrderPoint::select('user_code', 'sponsor_code', 'created_at')
            ->distinct()
            ->where("sponsor_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::PATROCINIO ])
            ->where("payment" , true)
            ->get();

        if( $paymentOrderPoints != null ){
            $sponsorBroather = "";
            foreach ($paymentOrderPoints as $key => $paymentOrder) {
                if( !$this->maxChilds($paymentOrder->user_code) ) return $paymentOrder->user_code;
            }

            foreach ($paymentOrderPoints as $key => $paymentOrder){
                if( !$this->maxChilds($paymentOrder->user_code) ) continue;
                return $this->verifyChildNewSponsor($paymentOrder->user_code);
            }
        }
        
        return $userCode;
    }
}