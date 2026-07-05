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
use App\Models\UserEmailTemp;
use App\Models\PaymentProductOrder;

use App\Models\ReportUserNew;
use App\Models\ReportUserGroup;


use Exception;

use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExcelUsers;

class UserChangePassword extends Command
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
    protected $signature = 'app:user-change-password';

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
        try{

            $this->info("Inicio...........");

            $userList = User::with(['range.range'])->get();

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();

            $fechaActual = Carbon::now();

            // Obtener mes y año
            $oneMonthAgo = $fechaActual->subMonth();

            // Obtener mes y año
            $mes = $oneMonthAgo->translatedFormat('F'); // o 'F' para nombre del mes
            $año = $oneMonthAgo->format('Y');
            $month = $oneMonthAgo->format('m');

            $userAdmin = User::where("is_admin" , true)->first();

            $tempUser = UserEmailTemp::where("userId", $userAdmin->id)
                ->where("month", $oneMonthAgo->format('m'))
                ->where("year", $oneMonthAgo->format('Y'))->first();

            $userList = unserialize($tempUser->jsonBody);

            foreach ($userList as $keyTemp => $_user){
                $_user = (object) $_user;
                $childs = $this->listUserChildsDirect( $_user->uuid );

                $userListFilter = array_filter($userList, fn($n) => in_array( $n->uuid, $childs));

                $userListFilterMaxActive = array_filter($userListFilter, fn($n) => $n->status == 'Activo');

                $__u = User::where("uuid" , $_user->uuid)->first();
                
                ReportUserNew::create(array(
                    "userId" => $__u->id,
                    "countChildren" => count($userListFilterMaxActive),
                    "codeUsers" => implode(",", array_map(fn($u) => $u->uuid, $userListFilterMaxActive) ),
                ));


                $childsAll = $this->listUserChilds( $_user->uuid , array() );

                $userListFilterAll = array_filter($userList, fn($n) => in_array( $n->uuid, $childs));

                $userMax = array_reduce($userListFilterAll, function($a, $b) {
                    return ($a === null || $a->points->pointGroup > $b->points->pointGroup) ? $a : $b;
                });

                $userMin = array_reduce($userListFilterAll, function($a, $b) {
                    return ($a === null || $a->points->pointGroup < $b->points->pointGroup) ? $a : $b;
                });

                $_userMax = User::where("uuid" , $userMax->uuid)->first();

                $_userMin = User::where("uuid" , $userMin->uuid)->first();

                ReportUserGroup::create(array(
                    "userId" => $__u->id,
                    "maxGroupUserId" => $_userMax->id,
                    "maxGroupPoint" => $_userMax->points->pointGroup,
                    "minGroupUserId" => $_userMin->id,
                    "minGroupPoint" => $_userMin->points->pointGroup,
                ));
            }

            $this->info("Fin...........");
        }catch (Exception $e){
            $this->info("Error: {$e->getMessage()}");
        }

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

    public function listUserChilds( $sponsorCode , $a_listUser )
    {
        $pointOrders = PaymentOrderPoint::select("user_code", "sponsor_code", "type")
            ->where("sponsor_code", $sponsorCode)
            ->where("type", PaymentOrderPoint::COMPRA)
            ->distinct()->get();

        foreach ($pointOrders as $key => $pointOrder) {
            // $user = User::where("uuid", $pointOrder->user_code)->first();
            // array_push($a_listUser, $user);
            array_push($a_listUser, $pointOrder->user_code);
            $a_listUser = $this->listUserChilds( $pointOrder->user_code , $a_listUser );
        }
        return $a_listUser;
    }


    public function listUserChildsDirect($uuid){
        $pointOrders = PaymentOrderPoint::select("user_code", "sponsor_code", "type")
            ->where("sponsor_code", $uuid)
            ->where("type", PaymentOrderPoint::COMPRA)
            ->distinct()->get();
        $_a = array();
        foreach ($pointOrders as $key => $pointOrder){
            array_push($_a, $pointOrder->user_code);
        }
        return $_a;

    }

}
