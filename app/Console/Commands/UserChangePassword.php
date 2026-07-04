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
        $userList = User::with(['range.range'])->get();

        $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();

        $fechaActual = Carbon::now();

        // Obtener mes y año
        $oneMonthAgo = $fechaActual->subMonth();

        // Obtener mes y año
        $mes = $oneMonthAgo->translatedFormat('F'); // o 'F' para nombre del mes
        $año = $oneMonthAgo->format('Y');
        $month = $oneMonthAgo->format('m');


        $subject = "Resumen General de puntos y bonos del último mes - Vithara";

        foreach ($userList as $key => $user) {
            if( $user->is_admin ){
                // ==== SOLO PARA EL ADMIN
                $jsonBody = array();
                foreach ($userList as $keyTemp => $_user){
                    $_user = (object) $_user;
                    $_user->payment = PaymentLog::with(['paymentOrder.pack' ])->where( "user_id" ,  $_user->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                    $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $_user->id)->where("state" , true)->get();

                    $calculator = $this->calculator->points( $_user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                    $calculatorPoint = $this->calculator->pointsTotal( $_user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );

                    array_push( $jsonBody , (object) array(
                        "fullname" => $_user->name,
                        "email" => $_user->email,
                        "uuid" => $_user->uuid,
                        "pack" => $_user->payment?->paymentOrder?->pack?->title ?? "Sin Plan",
                        "status" => $_user->payment == null ? "--" : ( $_user->payment->state == PaymentLog::PAGADO ? "Activo" : "Inactivo" ),
                        "totalPoint" => $calculatorPoint,
                        "range" => $_user->range == null ? "Sin Rango" : $_user->range->range->title,
                        "points" => (object) array(
                            "patrocinio"    => $calculator->patrocinio,
                            "residual"      => $calculator->residual,
                            "compra"        => $calculator->compra,
                            "pointGroup"    => $calculator->pointGroup,
                            "personal"      => $calculator->personal,
                            "infinito"      => $calculator->infinito,
                            "pointAfiliado" => $calculator->pointAfiliado,
                            "personalGlobal" => $calculator->personalGlobal,
                            "residualTotal" => $calculator->residualTotal ?? 0,
                            "currentPack"    => $calculator->currentPack ?? 0,
                            "residualVolumen" => 0
                        ),
                    ) );
                }



                // crear archivo excel
                $excelBody = array();

                foreach ($jsonBody as $key => $json) {
                    array_push(
                        $excelBody,
                        array(
                            $json->fullname,
                            $json->uuid,
                            $json->status,
                            $json->pack,
                            $json->points?->pointAfiliado ?? 0,
                            $json->points?->patrocinio ?? 0,
                            $json->points?->residualTotal ?? 0,
                            ( ($json->points?->patrocinio ?? 0)
                                + ($json->points?->residualTotal ?? 0)
                            ),
                            $json->points?->compra ?? 0,
                            $json->points->personal ?? 0,
                            $json->points->infinito ?? 0,
                            $json->totalPoint,
                            $json->range
                        )
                    );
                }

                // 1. Guardar Excel
                $fecha = Carbon::now()->format('YmdHis');
                $nameFile = "exports/reporte_usuarios_{$fecha}.xlsx";

                Excel::store(new ReportExcelUsers($excelBody), $nameFile);

                $userTemp = UserEmailTemp::create(array(
                    'userId' => $user->id,
                    'isAdmin' => $user->is_admin,
                    'status' => UserEmailTemp::PENDIENTE,
                    'email' => $user->email,
                    'subject' => $subject . " ". strtoupper($mes) ."-".$año ,
                    'month'=> $month,
                    'year'=> $año,
                    'jsonBody'=> serialize($jsonBody),
                    'fileAttachment' => $nameFile,
                ));
            }
        }

    }
}
