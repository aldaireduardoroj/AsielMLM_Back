<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Range;
use App\Models\ScheduleCron;
use App\Models\PaymentOrderPoint;
use App\Models\RangeUser;
use App\Models\PaymentProductOrderPoint;
use App\Services\Core\Calculator;
use App\Models\PaymentLog;

use App\Models\RangeResidualPoints;
use App\Models\GeneratonialResidualPoints;

class RangeListBulk extends Command
{

    
    private $calculator;

    public function __construct()
    {
        parent::__construct();
        $this->calculator = new Calculator();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:range-list-bulk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $scheduleCron = ScheduleCron::create(array(
            'signature' => "app:range-list-bulk",
        ));
        try {
            DB::beginTransaction();

            $userModel = User::where("is_admin", 1)->first();

            $userAll = User::with(['paymentActive'])->where("is_admin", 0)->get();

            $listRange = Range::where("state", 1)->orderBy('order', 'ASC')->get();

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog'])->where('state' , true)->get();

            $userTreesList = PaymentOrderPoint::with(['user.paymentActive'])->whereIn("type", [PaymentOrderPoint::PATROCINIO])
                ->where("payment" , 1)->get();

            $userTreesListSponsor = PaymentOrderPoint::with([ 'user.paymentActive'])->whereIn("type", [PaymentOrderPoint::PATROCINIO])
                ->where("payment" , 1)->get();

            RangeUser::where("status", 1)->update(array("status" => 0));

            foreach ($userAll as $keyUser => $user)
            {
                $rangeUser = RangeUser::where("user_id", $user->id )->first();
                if( $user->paymentActive == null ){
                    if( $rangeUser != null ){
                        RangeUser::where("user_id", $user->id)->update(array("range_id" => 1, "status" => 0));
                    }
                }
                
                if( $rangeUser == null ){
                    RangeUser::create(array(
                        "user_id" => $user->id, "range_id" => 1, "status" => 1
                    ));
                }
                // else{
                //     RangeUser::where("user_id", $user->id)->update(array("range_id" => 1, "status" => 1));
                // }
            }

            $response = array();

            foreach ($listRange as $keyRange => $range) {
                $range = (object) $range;

                foreach ($userTreesList as $keyUser => $userPoint)
                {
                    if( $userPoint->user->paymentActive == null ) continue;

                    $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $userPoint->user->id)->where("state" , true)->get();
                    $totalPoints = $this->calculator->pointsTotal( $userPoint->user->uuid , $paymentOrderPoints , $paymentProductOrderPoints);

                    $points = $this->calculator->points( $userPoint->user->uuid , $paymentOrderPoints , $paymentProductOrderPoints);

                    $countChild = 0;
                    
                    $_userTreesList = PaymentOrderPoint::with(['user.paymentActive'])->where("sponsor_code" , 'like', $userPoint->user->uuid)
                    ->whereIn("type", [PaymentOrderPoint::PATROCINIO])
                    ->where("payment" , 1)->get();

                    // foreach ($_userTreesList as $key => $_user) {
                    //     if( $_user->user->paymentActive != null ) $countChild++;
                    // }
                    $countActive = $this->createActiveDirect($userPoint->user->uuid);
                    if( $range->id == 1){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 1 && ( $points->pointGroup >= 2000 && $userPoint->user->paymentActive != null) ) );
                    
                    }else if( $range->id == 2){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 2 && ( $points->pointGroup >= 4000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 3){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 3 && ( $points->pointGroup >= 8000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 4){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 3 && ( $points->pointGroup >= 18000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 5){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 3 && ( $points->pointGroup >= 40000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 6){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 4 && ( $points->pointGroup >= 100000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 7){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 4 && ( $points->pointGroup >= 210000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 8){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 5 && ( $points->pointGroup >= 380000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 9){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 5 && ( $points->pointGroup >= 640000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 10){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 6 && ( $points->pointGroup >= 1250000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 11){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 6 && ( $points->pointGroup >= 2500000 && $userPoint->user->paymentActive != null) ) );

                    }else if( $range->id == 12){
                        $this->createUpdateRangeUser( $userPoint->user->id , $range->id, ($countActive >= 7 && ( $points->pointGroup >= 5000000 && $userPoint->user->paymentActive != null) ) );

                    }
                }

            }

            $infinito = array();

            DB::commit();

            ScheduleCron::where("id", $scheduleCron->id)->update(array(
                "response" => json_encode( array("range" => $response, "infinito" => $infinito ) ),
                "status" => 2
            ));
        } catch (\Exception $e) {
            DB::rollBack();

            ScheduleCron::where("id", $scheduleCron->id)->update(array(
                "status" => 3,
                "response" => $e->getMessage()
            ));
        }
    }

    private function loopTree( string $userCode )
    {
        $paymentOrderPoints = PaymentOrderPoint::with(['user.paymentActive'])->where("sponsor_code" , 'like', $userCode)
        ->whereIn("type", [PaymentOrderPoint::PATROCINIO])
        ->where("payment" , 1)->get();

        $a_paymentOrderPoint = array();

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint) {
            $paymentOrderPoint = (object) $paymentOrderPoint;

            $paymentOrderPoint->childs = $this->loopTree( $paymentOrderPoint->user_code );
            array_push($a_paymentOrderPoint , $paymentOrderPoint);
        }

        return $a_paymentOrderPoint;
    }

    private function loopTreeActive( $a_paymentOrderPoint = array() , string $userCode )
    {
        $paymentOrderPoints = PaymentOrderPoint::with(['user.paymentActive'])->where("sponsor_code" , 'like', $userCode)
        ->whereIn("type", [PaymentOrderPoint::PATROCINIO])
        ->where("payment" , 1)->get();

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint)
        {
            $paymentOrderPoint = (object) $paymentOrderPoint;
            array_push($a_paymentOrderPoint, $paymentOrderPoint);

            $a_paymentOrderPoint = $this->loopTreeActive( $a_paymentOrderPoint, $paymentOrderPoint->user_code );
        }

        return $a_paymentOrderPoint;
    }

    private function countTreeRange( string $userCode , $rangeId)
    {
        $paymentOrderPoints = $this->loopTreeActive( array(), $userCode);

        $a_paymentOrderPoint = array();

        $count = 0;

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint) {
            $paymentOrderPoint = (object) $paymentOrderPoint;
            $rangeUser = RangeUser::where("user_id", $paymentOrderPoint->user->id )->where("status" , 1)->first();
            if( $rangeUser == null ) continue;
            if( $rangeUser->range_id == $rangeId && $paymentOrderPoint->user->paymentActive != null) $count++;

            $count += $this->countTreeRange( $paymentOrderPoint->user_code, $rangeId );

            array_push($a_paymentOrderPoint , $paymentOrderPoint);
        }

        return $count;
    }

    private function countTreeRangeDirect(string $userCode , $rangeId)
    {
        $paymentOrderPoints = PaymentOrderPoint::with(['user.paymentActive'])->where("sponsor_code" , 'like', $userCode)
        ->whereIn("type", [PaymentOrderPoint::PATROCINIO])
        ->where("payment" , 1)->get();

        $count = 0;

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint) {
            $paymentOrderPoint = (object) $paymentOrderPoint;
            $rangeUser = RangeUser::where("user_id", $paymentOrderPoint->user->id )->where("status" , 1)->first();
            if( $rangeUser == null ) continue;
            if( $rangeUser->range_id == $rangeId && $paymentOrderPoint->user->paymentActive != null) $count++;

        }
        
        return $count;
    }

    private function createUpdateRangeUser( $userId, $rangeId, $active)
    {
        if( $active ){
            // $orders = PaymentLog::where("user_id", $userId)->where( "state", PaymentLog::TERMINADO)->count();
            // if( $orders > 0 ){
                
            // }
            $rangeUser = RangeUser::where("user_id", $userId )->first();
            if( $rangeUser == null ){
                RangeUser::create(array(
                    "user_id" => $userId, "range_id" => $rangeId, "status" => 1
                ));
            }else{
                if( $rangeUser->range_id != $rangeId ){
                    $generatonialResidualPoint = GeneratonialResidualPoints::where("user_id", $userId)->where("range_id", $rangeUser->range_id)->first();
                    if( $generatonialResidualPoint != null ){
                        $rangeResidualPoint = RangeResidualPoints::where("range_id", $rangeId)->first();

                        $percentage = $rangeResidualPoint->{'level'.($generatonialResidualPoint->level)};

                        PaymentOrderPoint::where("id", $generatonialResidualPoint->point_id)->update(
                            array("point" => ( $generatonialResidualPoint->points * $percentage / 100 ))
                        );
                    }
                    RangeUser::where("user_id", $userId)->update(array("range_id" => $rangeId, "status" => 1));
                }
                // RangeUser::where("user_id", $userId)->update(array("range_id" => $rangeId, "status" => 1));
                
            }
        }
    }

    private function loopTreeLevels( array $a_paymentOrderPoint , string $userCode )
    {
        $paymentOrderPoint = PaymentOrderPoint::select('user_code', 'sponsor_code')
            ->distinct()
            ->where("user_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::PATROCINIO ])
            ->where("payment" , 1)
            ->first();

        if( $paymentOrderPoint != null ){
            array_push( $a_paymentOrderPoint , $paymentOrderPoint  );

            $a_paymentOrderPoint = $this->loopTreeLevels( $a_paymentOrderPoint , $paymentOrderPoint->sponsor_code );

        }

        return $a_paymentOrderPoint;
    }

    private function loopTreeBonoInifity(array $paymentOrderPoints ,$points , $nivel, $totalPoint)
    {
        $nivel++;
        foreach ($paymentOrderPoints as $key => $paymentOrderPoint){
            if( $nivel >= 8 ){
                $granTotal = 0;
                if( $paymentOrderPoint->user?->paymentActive != null ){
                    $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $paymentOrderPoint->user->id)->where("state" , true)->get();
                    $granTotal = $this->calculator->pointsTotal( $paymentOrderPoint->user_code , $points , $paymentProductOrderPoints);
                }
                $totalPoint += $granTotal;
            }
            $totalPoint = $this->loopTreeBonoInifity( $paymentOrderPoint->childs, $points, $nivel, $totalPoint);
        }

        return $totalPoint;

    }

    private function loopTreeVerifyRangeMax(array $paymentOrderPoints , $range, $isRangeMax)
    {
        foreach ($paymentOrderPoints as $key => $paymentOrderPoint){
            if( $paymentOrderPoint->user?->paymentActive != null ){
                $rangeUser = RangeUser::with(['range'])->where("user_id", $paymentOrderPoint->user->id )->where("status" , 1)->first();
                if( $rangeUser == null ) continue;
                if( $range > $rangeUser->range->order ){
                    $isRangeMax = true;
                }
                $isRangeMax = $this->loopTreeVerifyRangeMax($paymentOrderPoint->childs, $range, $isRangeMax);
            }
        }

        return $isRangeMax;
    }

    private function createActiveDirect($userCode)
    {
        $paymentOrderPoints = PaymentOrderPoint::with([])->where("sponsor_code" , 'like', $userCode)
        ->whereIn("type", [PaymentOrderPoint::PATROCINIO])
        ->where("payment" , 1)->get();

        $count = 0;

        foreach ($paymentOrderPoints as $key => $paymentOrderPoint)
        {
            $_user = User::with(['paymentActive'])->where("uuid", $paymentOrderPoint->user_code )->first();
            if( $_user->paymentActive != null){
                $count++;
            }
        }

        return $count;
    }
}
