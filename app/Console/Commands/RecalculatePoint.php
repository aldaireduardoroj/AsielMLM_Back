<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\Range;
use App\Models\ScheduleCron;
use App\Models\PaymentOrderPoint;
use App\Models\RangeUser;
use App\Models\PaymentProductOrderPoint;
use App\Services\Core\Calculator;
use App\Models\PaymentLog;
use App\Models\UserEmailTemp;
use App\Models\PaymentProductOrder;
use App\Models\RangeResidualPoints;
use App\Models\GeneratonialResidualPoints;


class RecalculatePoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-point';

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
        $this->info("Inicio del proceso de recalculo de puntos...");

        $paymentOrderPointUsers = PaymentOrderPoint::with([])
            ->where('state' , true)
            ->where('type' , PaymentOrderPoint::RESIDUAL)
            ->pluck('user_id')
            ->toArray();

        $userList = User::with(['range.range'])
            ->whereIn('id', $paymentOrderPointUsers)
            ->get();

        foreach ($userList as $key => $user) {
            $this->info("Usuario: {$user->name} - {$user->uuid} - {$user->id} - {$user->range?->range?->id}");
            if( !$user->is_admin ){
                $this->info("Inicio recalculo...");
                $_paymentOrderPoints = $this->loopTree( array() , $user->uuid );

                $rangeResidualPoint = RangeResidualPoints::where("range_id", $user?->range?->range?->id ?? 1)->first();
                
                $paymentOrderPointUser = PaymentOrderPoint::with([])
                    ->where('state' , true)
                    ->where('type' , PaymentOrderPoint::RESIDUAL)
                    ->where('user_id' , $user->id)
                    ->where('user_code' , $user->uuid)
                    ->first();

                $generatonialResidualPoint = GeneratonialResidualPoints::where("user_id", $user->id)
                    ->where('point_id', $paymentOrderPointUser->id)
                    ->first();

                PaymentOrderPoint::where('state' , true)
                    ->where('type' , PaymentOrderPoint::RESIDUAL)
                    ->where('user_id' , $user->id)
                    ->update(array(
                        'state' => false
                    ));
                GeneratonialResidualPoints::where("user_id", $user->id)->update(array(
                    'state' => false
                ));

                foreach ($_paymentOrderPoints as $keyPoi => $_paymentOrderPoint)
                {
                    $_paymentOrderPoint = (object) $_paymentOrderPoint;
                    $keyPoi++;
                    if( $keyPoi > 9 ) continue;

                    $level = $rangeResidualPoint->{'level'.($keyPoi)};

                    $point = $level == 0 ? 0 : ($generatonialResidualPoint->points * floatval($level) / 100);

                    $__paymentOrderPoint = PaymentOrderPoint::create(array(
                        'payment_order_id' => $paymentOrderPointUser->payment_order_id,
                        'user_code' => $_paymentOrderPoint->user_code,
                        'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                        'point' => $point,
                        'payment' => false,
                        'type' => PaymentOrderPoint::RESIDUAL,
                        'user_id' => $user->id
                    ));

                    GeneratonialResidualPoints::create(array(
                        'user_id' => $user->id,
                        'range_id' => $user?->range?->range?->id ?? 0,
                        'point_id' => $__paymentOrderPoint->id,
                        'points'    => $generatonialResidualPoint->points,
                        'level' => $keyPoi
                    ));
                }
            }
        }

        $this->info("Fin............");
    }

    private function loopTree( array $a_paymentOrderPoint , string $userCode )
    {
        $paymentOrderPoint = PaymentOrderPoint::select('user_code', 'sponsor_code')
            ->distinct()
            ->where("user_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::COMPRA ])
            ->where("payment" , true)
            ->first();

        if( $paymentOrderPoint != null ){
            array_push( $a_paymentOrderPoint , $paymentOrderPoint  );

            $a_paymentOrderPoint = $this->loopTree( $a_paymentOrderPoint , $paymentOrderPoint->sponsor_code );
            
        }

        return $a_paymentOrderPoint;
    }
}
