<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use App\Models\PaymentLog;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPoint;
use App\Services\Core\Calculator;
use App\Models\PaymentProductOrderPoint;

class PaymentOrderPointController extends BaseController
{
    //
    private $calculator;

    public function __construct() {
        $this->calculator = new Calculator();
    }

    public function points()
    {
        try {
            $user_id = Auth::id();


            $paymentOrderPoint = PaymentOrderPoint::with(['paymentOrder.paymentLog', 'userPoint.paymentActive'])->where('state' , true)->get();

            return $this->sendResponse( $paymentOrderPoint , 'User');
        } catch (Exception $e) {
            return $this->sendError( $e->getMessage() );
        }
    }

    public function pointsUser()
    {
        try {
            $user_id = Auth::id();

            $a_paymentOrderPoint = array();

            $userModel = User::with(['file','range.range.file'])->find($user_id);

            $payments = PaymentLog::with(['paymentOrder.pack'])->where( "user_id" ,  $user_id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

            $userModel->payment = $payments;

            // $paymentOrderPoint = PaymentOrderPoint::with(['paymentOrder.paymentLog','paymentOrder.pack'])->distinct()->get();
            $paymentOrderPoint = PaymentOrderPoint::select('user_code','sponsor_code','type','payment', 'created_at')->with(['paymentOrder.paymentLog','paymentOrder.pack'])->where("type" , PaymentOrderPoint::COMPRA )->distinct()->orderBy('created_at', 'desc')->get();
            // 'sponsor.file', 'user.file'

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog'])->where('state' , true)->orderBy('created_at', 'desc')->get();

            foreach ($paymentOrderPoint as $key => $popoint) {
                $_popoint = (object) $popoint;
                $_sponsor = User::with(['file'])->where("uuid" , 'like' , $popoint->sponsor_code)->first();
                $_sponsor->payment = PaymentLog::where( "user_id" ,  $_sponsor->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                $_popoint->sponsor = $_sponsor;

                $_user_code = User::with(['file','range.range.file'])->where("uuid" , 'like' , $popoint->user_code)->first();
                $_user_code->payment = PaymentLog::with(['paymentOrder.pack'])->where( "user_id" ,  $_user_code->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                
                $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $_user_code->id )->where("state" , true)->get();
                $pointTotal = $this->calculator->pointsTotal( $popoint->user_code , $paymentOrderPoints , $paymentProductOrderPoints);
                $points = $this->calculator->points( $popoint->user_code , $paymentOrderPoints , $paymentProductOrderPoints);
                $_popoint->pointTotal = $points->pointGroup;
                $_popoint->user = $_user_code;
                array_push( $a_paymentOrderPoint , $_popoint );

            }

            return $this->sendResponse( array( "points" => $a_paymentOrderPoint , "user" => $userModel ) , 'User');

        } catch (Exception $e) {
            return $this->sendError( $e->getMessage() );
        }
    }

}
