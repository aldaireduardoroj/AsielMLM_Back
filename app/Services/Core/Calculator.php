<?php

namespace App\Services\Core;

use App\Models\PaymentOrderPoint;
use App\Models\Option;
use App\Models\User;
use App\Models\PaymentLog;

class Calculator
{
    public function points( string $userCode , $paymentOrderPoints, $paymentProductOrderPoints): object
    {
        $patrocinio = 0;
        $residual = 0;
        $compra = 0;
        $pointGroup = 0;
        $personal = 0;
        $infinito = 0;
        $pointAfiliado = 0;

        $userModel = User::where("uuid" , $userCode)->first();
        $payment = PaymentLog::with(['paymentOrder.pack'])
                ->where( "user_id" ,  $userModel->id )
                ->where( function ($query) {
                    $query->where('state' , PaymentLog::PAGADO)
                    ->orWhere('state' , PaymentLog::TERMINADO);
                })
                ->orderBy('created_at', 'desc')
                ->first();

        $paymentCount = PaymentLog::with(['paymentOrder.pack'])
                ->where( "user_id" ,  $userModel->id )
                ->where( function ($query) {
                    $query->where('state' , PaymentLog::PAGADO)
                    ->orWhere('state' , PaymentLog::TERMINADO);
                })
                ->count();
        //

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint) {

            if( strtoupper($userCode) == strtoupper( $paymentOrderPoint->sponsor_code ) )
            {
                if( $paymentOrderPoint->type ==  PaymentOrderPoint::PATROCINIO ){
                    $patrocinio = $patrocinio + $paymentOrderPoint->point;
                }

                if( $paymentOrderPoint->type ==  PaymentOrderPoint::RESIDUAL ){
                    $residual = $residual + $paymentOrderPoint->point;
                }

                if( $paymentOrderPoint->type ==  PaymentOrderPoint::GRUPAL ){
                    $pointGroup = $pointGroup + $paymentOrderPoint->point;
                }

                if( $paymentOrderPoint->type ==  PaymentOrderPoint::AFILIADOS ){
                    $pointAfiliado = $pointAfiliado + $paymentOrderPoint->point;
                }
            }
            if( strtoupper($userCode) == strtoupper( $paymentOrderPoint->user_code ) ){
                if( $paymentOrderPoint->type ==  PaymentOrderPoint::COMPRA ){
                    $compra = $compra + $paymentOrderPoint->point;
                }
            }

            if( strtoupper($userCode) == strtoupper( $paymentOrderPoint->sponsor_code ) ){
                if( $paymentOrderPoint->type ==  PaymentOrderPoint::INFINITO ){
                    $infinito = $infinito + $paymentOrderPoint->point;
                }
            }
        }

        foreach ($paymentProductOrderPoints as $key => $paymentProductOrderPoint) {
            $personal = $personal + $paymentProductOrderPoint->points;
        }

        $optionBono = Option::where("option_key", 'bono_global')->first();

        // $personalGlobal = $paymentCount > 1 ? ($payment?->state == PaymentLog::PAGADO ? ($payment?->paymentOrder?->pack?->id == $optionBono->option_value ? $personal * 0.02 : 0) : 0) : 0;
        $personalGlobal = 0;

        return (object) array(
            "patrocinio"    => $patrocinio,
            "residual"      => $residual,
            "compra"        => $compra,
            "pointGroup"    => $pointGroup,
            "personal"      => $personal,
            "infinito"      => $infinito,
            "pointAfiliado" => $pointAfiliado,
            "personalGlobal" => $personalGlobal
        );
    }

    public function pointsTotal( string $userCode , $paymentOrderPoints, $paymentProductOrderPoints)
    {
        $calculatorPoint = $this->points( $userCode , $paymentOrderPoints, $paymentProductOrderPoints);
        return $calculatorPoint->patrocinio +
            $calculatorPoint->residual +
            $calculatorPoint->compra +
            $calculatorPoint->pointGroup +
            $calculatorPoint->personal +
            $calculatorPoint->infinito +
            $calculatorPoint->pointAfiliado +
            $calculatorPoint->personalGlobal;
    }
}
