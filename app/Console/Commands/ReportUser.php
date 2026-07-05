<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;
use App\Models\ReportUserNew;
use App\Models\ReportUserGroup;
use App\Models\ScheduleCron;
use App\Models\PaymentOrderPoint;
use App\Services\Core\Calculator;

use Exception;

class ReportUser extends Command
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
    protected $signature = 'app:report-user';

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
        $scheduleCron = ScheduleCron::create(array(
            'signature' => "app:report-user",
        ));
        try {
            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();

            $users = User::all();
            foreach ($users as $key => $user) {
                // report user new
                $pointOrders = PaymentOrderPoint::with("user")
                    ->whereHas("user", function($query) {
                        $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    })
                    ->where("sponsor_code", $user->uuid)
                    ->where("type", PaymentOrderPoint::COMPRA)
                    ->distinct()->get();
                $countChild = count($pointOrders);
                if(  $countChild > 0 ){
                    $a_users = array();
                    foreach ($pointOrders as $keyP => $pointOrder) {
                        array_push($a_users, $pointOrder->user->uuid);
                    }
                    ReportUserNew::create(array(
                        "userId" => $user->id,
                        "countChildren" => $countChild,
                        "codeUsers" => implode(",", $a_users),
                    ));
                }

                // report user group

                $childs = $this->listUserChilds( $user->uuid , array() );
                if( count( $childs ) > 0 ){
                    $_userResult = array();
                    foreach ($childs as $keyC => $child) {
                        $pointCalculator = $this->calculator->points( $child->uuid , $paymentOrderPoints , array());
                        $_userResult[$child->id] = $pointCalculator->pointGroup;
                    }
                    ReportUserGroup::create(array(
                        "userId" => $user->id,
                        "maxGroupUserId" => array_search( max($_userResult) , $_userResult ),
                        "maxGroupPoint" => max($_userResult),
                        "minGroupUserId" => array_search( min($_userResult) , $_userResult ),
                        "minGroupPoint" => min($_userResult),
                    ));
                }
            }



            ScheduleCron::where("id", $scheduleCron->id)->update(array(
                "response" => json_encode( array() ),
                "status" => 2
            ));
        } catch (Exception $e){
            ScheduleCron::where("id", $scheduleCron->id)->update(array(
                "status" => 3,
                "response" => $e->getMessage()
            ));
        }
    }

    public function listUserChilds( $sponsorCode , $a_listUser )
    {
        $pointOrders = PaymentOrderPoint::select("user_code", "sponsor_code", "type")
            ->where("sponsor_code", $sponsorCode)
            ->where("type", PaymentOrderPoint::COMPRA)
            ->distinct()->get();

        foreach ($pointOrders as $key => $pointOrder) {
            $user = User::where("uuid", $pointOrder->user_code)->first();
            array_push($a_listUser, $user);
            $a_listUser = $this->listUserChilds( $pointOrder->user_code , $a_listUser );
        }
        return $a_listUser;
    }


}