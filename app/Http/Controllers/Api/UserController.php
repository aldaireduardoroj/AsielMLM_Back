<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentLog;
use App\Services\Core\FileUpload;
use App\Http\Resources\PaginationCollection;
use App\Models\Pack;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPoint;
use App\Models\Range;
use App\Services\Core\Calculator;
use App\Models\PaymentProductOrderPoint;
use App\Models\PaymentProductOrder;
use App\Models\SponsorshipPoint;
use App\Models\ResidualPoint;
use App\Models\RangeUser;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductPointPack;
use Maatwebsite\Excel\Excel as BaseExcel;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersPointExport;
use App\Mail\UsersPointExcel;
use App\Mail\UserPointActive;
use App\Models\PaymentProductOrderDetail;

use App\Models\InviteUser;
use App\Models\GuestsTokenUser;
use App\Models\AfiliadosPoint;

use Illuminate\Support\Facades\Mail;
use App\Models\UserEmailTemp;

use App\Exports\ReportExcelUsers;
use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\Core\ConfirmPointService;

use App\Mail\InivitedSponsorUser;
use App\Services\Core\CodeGenerator;
use App\Models\VerificationCodeUser;
use App\Models\VideoImageStory;

use App\Models\RangeResidualPoints;
use App\Models\GeneratonialResidualPoints;


class UserController extends BaseController
{
    //

    private $fileUpload;
    private $fileUploadPath;
    private $calculator;
    private $confirmPointService;

    private $videoStoryUploadPath;
    private $imageStoryUploadPath;
    private $videoPreviewStoryUploadPath;

    public function __construct() {
        $this->fileUpload = new FileUpload();
        $this->fileUploadPath = 'avatar';
        $this->videoStoryUploadPath = 'video-story';
        $this->videoPreviewStoryUploadPath = 'video-preview-story';
        $this->imageStoryUploadPath = 'image-story';
        $this->calculator = new Calculator();
        $this->confirmPointService = new ConfirmPointService();
    }

    public function auth()
    {
        try {
            $user_id = Auth::id();

            $userModel = User::with(['file','range.range.file'])->select("*" , "created_at as creatxlssed")->find($user_id);
            $payment = PaymentLog::with(['paymentOrder.pack'])
                    ->where( "user_id" ,  $user_id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

            $uuid = $userModel->uuid;

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog'])->where('state' , true)->get();
            $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $user_id)->where("state" , true)->get();

            $ranges = Range::where("state" , true)
                ->orderBy('points', 'asc')
                ->get();
            $calculatorPoint = $this->calculator->points( $userModel->uuid , $paymentOrderPoints , $paymentProductOrderPoints);
            $totalPoints = $calculatorPoint->patrocinio + $calculatorPoint->residual + $calculatorPoint->compra + $calculatorPoint->pointGroup + $calculatorPoint->personal;

            $_paymentOrderPoints = array_filter( $paymentOrderPoints->toArray() ,
                function($p)use($uuid) { return strtoupper($p['sponsor_code']) == strtoupper($uuid) && $p['state'] == true && $p['payment'] == true && $p['type'] != PaymentOrderPoint::GRUPAL; }
            );


            $userModel->payment = $payment;
            $userModel->podints = $calculatorPoint;

            return $this->sendResponse( $userModel , 'User');
        } catch (Exception $e) {
            return $this->sendError( $e->getMessage() );
        }
    }

    public function authUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [

        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            DB::beginTransaction();

            $dataBody = (object) $request->all();
            $user_id = Auth::id();
            User::where("id" , $user_id)->update(array(
                "address"   => $dataBody->address,
                "phone"     => $dataBody->phone,
                'city'      => $dataBody->city,
                'country'   => $dataBody->country,
                'genger'    => $dataBody->gender,
            ));

            DB::commit();
            return $this->sendResponse( true , 'User');
        } catch (Exception $e) {
            return $this->sendError( $e->getMessage() );
        }
    }

    public function authUpdateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:5120',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            DB::beginTransaction();

            $fileId = 0;

            $user_id = Auth::id();


            if($request->hasfile('file')) $fileId = $this->fileUpload->upload( $request->file('file') , $this->fileUploadPath);

            User::where("id" , $user_id)->update(array(
                "photo" => $fileId,
            ));

            $userModel = User::with(['file'])->find($user_id);

            DB::commit();
            return $this->sendResponse( $userModel , 'User');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError( $e->getMessage() );
        }
    }

    public function findAll(Request $request)
    {
        try {
            $limit = $this->limit;

            if( $request->has('limit') ) $limit = intval( $request->query('limit') );

            $muscleGroupList = array();

            $user_id = Auth::id();
            $userModel = User::with(['file'])->find($user_id);

            if( !$userModel->is_admin ) return $this->sendError( "No tiene permisos ese usuario" );
            // $userDetail = UserDetail::where("user_id" , $user_id)->first();

            $userList = User::with(['file','range.range.file'])->where('is_admin', false);

            // if( $request->has('code') ) $muscleGroupList = $request->query('status') != NULL ? $muscleGroupList->where("status" , $request->query('status') ) : $muscleGroupList;
            if( $request->has('code') ) if( !empty($request->query('code')) ) $userList = $userList->where("uuid" , 'like' , $request->query('code') );
            if( $request->has('email') ) if( !empty($request->query('email')) )$userList = $userList->where("email" , 'like' , $request->query('email') );
            if( $request->has('name') ) if( !empty($request->query('name')) ) $userList = $userList->where("name" , 'like' , '%'.( $request->query('name') ).'%' );

            if( $request->has('plan') ){
                if( !empty($request->query('plan')) ){
                    $plan = $request->query('plan');

                    if( $plan == -1 ){
                        $user_payments = PaymentLog::with(['paymentOrder'])->where( function ($query) {
                            $query->where('state' , PaymentLog::PAGADO)
                            ->orWhere('state' , PaymentLog::TERMINADO);
                        })->pluck('user_id')->toArray();

                        $userList = $userList->whereNotIn("id" , $user_payments);
                    }else{
                        $user_payments_pack = PaymentLog::with(['paymentOrder.pack' ])->where( function ($query) {
                            $query->where('state' , PaymentLog::PAGADO)
                            ->orWhere('state' , PaymentLog::TERMINADO);
                        })->whereHas("paymentOrder.pack" , function( $query ) use ($plan) { $query->where('id', $plan ); } )->pluck('user_id')->toArray();
                        $userList = $userList->whereIn("id" , $user_payments_pack);
                    }
                }
            }

            $userList = $userList->orderBy('created_at', 'desc')->paginate($limit);

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();

            foreach ($userList as $key => $user) {
                $userList[$key]->payment = PaymentLog::with(['paymentOrder.pack' , 'paymentOrder.sponsor.file'])->where( "user_id" ,  $user->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::PREORDER)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                $_id = $user->id;
                $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $user->id)->where("state" , true)->get();

                $calculatorPoint = $this->calculator->pointsTotal( $user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                
                $userList[$key]->points = $this->calculator->points( $user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                $userList[$key]->totalPoints = $calculatorPoint;
            }

            // $userList = $userList

            return $this->sendResponse( new PaginationCollection($userList), 'Lista');

        } catch (\Throwable $th) {

            return $this->sendError( $th->getMessage());
        }
    }

    public function modifyUser( Request $request )
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
            'userFullName' => 'required'
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            $user_id = Auth::id();
            DB::beginTransaction();
            $userModel = User::with(['file'])->find($user_id);

            if( !$userModel->is_admin ) return $this->sendError( "No tiene permisos ese usuario" );

            $dataBody = (object) $request->all();

            $userUpdated = User::where("uuid" , $dataBody->userCode)->first();

            if( $userUpdated == null ) return $this->sendError( "No se existe el usuario seleccionado" );

            User::where("uuid" , $dataBody->userCode)->update(
                array( "name" => $dataBody->userFullName )
            );

            $ischange = false;

            $paymentLogOld = PaymentLog::with(['paymentOrder'])->where("user_id" ,  $userUpdated->id )->whereIn("state" , [ PaymentLog::PAGADO ,  PaymentLog::TERMINADO])->orderBy('created_at', 'desc')->first();

            $paymentLogs = PaymentLog::with(['paymentOrder'])
                ->where( "user_id" ,  $user_id )
                ->where( function ($query) {
                    $query->where('state' , PaymentLog::PAGADO)
                    ->orWhere('state' , PaymentLog::TERMINADO);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            $paymentLogs = (object) $paymentLogs;

            if( $paymentLogOld != null ){
                $paymentOrderOld = PaymentOrder::where("id" ,  $paymentLogOld->payment_order_id )->first();

                if( $paymentOrderOld != null ){
                    if( $paymentOrderOld->pack_id != $dataBody->packId ) $ischange = true;
                }
            }else{
                if( $dataBody->packId != 1 ) $ischange = true;
            }

            if( $ischange ) {

                PaymentLog::where("user_id" ,  $userUpdated->id )->where("state" ,  PaymentLog::PAGADO )->update(
                    array(
                        "state" => PaymentLog::TERMINADO,
                    )
                );

                if( $dataBody->packId != 1 ){
                    $packCurrent = Pack::find($dataBody->packId);
                    if( $packCurrent == null ) return $this->sendError( "No se existe el plan seleccionado" );
                    $orderId = uniqid( $packCurrent->title );

                    if( !empty($dataBody->sponsorNew) ){

                        // if( $this->confirmPointService->maxChilds( $dataBody->sponsorNew ) ) return $this->sendError('Tu patrocinador esta al limite de invitados.');

                        $sponsorId = $this->confirmPointService->verifyChildNewSponsor( $dataBody->sponsorNew );
                        
                        $_paymentOrder = PaymentOrder::create(
                            array(
                                'currency' => "PEN",
                                'amount' => 0,
                                'sponsor_code' => $sponsorId,
                                'pack_id' => $packCurrent->id,
                                "token" => $orderId
                            )
                        );

                        $this->confirmPoint($_paymentOrder , $userUpdated , $packCurrent);

                        $_paymentLog = PaymentLog::create(
                            array(
                                'payment_order_id' => $_paymentOrder->id,
                                "confirm" => true,
                                'user_id' => $userUpdated->id,
                                "state" => PaymentLog::PAGADO,
                            )
                        );

                    }else{

                        $paymentOrder = PaymentOrder::create(
                            array(
                                'currency' => "PEN",
                                'amount' => 0,
                                'sponsor_code' => $paymentLogOld->paymentOrder->sponsor_code,
                                'pack_id' => $packCurrent->id,
                                "token" => $orderId
                            )
                        );

                        $paymentLog = PaymentLog::create(
                            array(
                                'payment_order_id' => $paymentOrder->id,
                                "confirm" => true,
                                'user_id' => $userUpdated->id,
                                "state" => PaymentLog::PAGADO,
                            )
                        );
                    }
                }else{
                    PaymentOrderPoint::where("user_id" , $userUpdated->id)
                        ->where("state", true)
                        ->update( array("state" => false) );

                    PaymentProductOrderPoint::where("user_id" , $userUpdated->id)
                        ->update( array( "state" => false ) );

                    PaymentProductOrder::where("user_id" , $userUpdated->id)
                        ->where("state", PaymentProductOrder::PAGADO)
                        ->update( array("state" => PaymentProductOrder::ANULADO) );
                }
            }

            DB::commit();
            return $this->sendResponse( array($userUpdated, $ischange) , 'User');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError( $e->getMessage() );
        }
    }

    public function search(Request $request)
    {
        try {

            $user_id = Auth::id();
            $userModel = User::with(['file'])->find($user_id);

            if( !$userModel->is_admin ) return $this->sendError( "No tiene permisos ese usuario" );
            // $userDetail = UserDetail::where("user_id" , $user_id)->first();

            $userList = User::with(['file']);

            // if( $request->has('code') ) $muscleGroupList = $request->query('status') != NULL ? $muscleGroupList->where("status" , $request->query('status') ) : $muscleGroupList;
            if( $request->has('code') ) if( !empty($request->query('code')) ) $userList = $userList->where("uuid" , 'like' , $request->query('code') );
            if( $request->has('email') ) if( !empty($request->query('email')) )$userList = $userList->where("email" , 'like' , $request->query('email') );
            if( $request->has('name') ) if( !empty($request->query('name')) ) $userList = $userList->where("name" , 'like' , '%'.( $request->query('name') ).'%' );

            $userList = $userList->orderBy('created_at', 'desc')->get();

            foreach ($userList as $key => $user) {
                $userList[$key]->payment = PaymentLog::with(['paymentOrder.pack'])->where( "user_id" ,  $user->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            return $this->sendResponse( $userList, 'Lista');

        } catch (\Throwable $th) {

            return $this->sendError( $th->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {

            $user_id = Auth::id();
            $userModel = User::with(['file'])->find($user_id);



            $userList = User::with(['file'])->where('is_admin', false)->get();

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog'])->where('state' , true)->get();
            $paymentProductOrderPoints = PaymentProductOrderPoint::where("state" , true)->get();

            $_userList = array();

            $ranges = Range::where("state" , true)
                ->orderBy('points', 'asc')
                ->get();

            foreach ($userList as $key => $user) {
                $payment = PaymentLog::with(['paymentOrder.pack'])->where( "user_id" ,  $user->id )
                    ->where( function ($query) {
                        $query->where('state' , PaymentLog::PAGADO)
                        ->orWhere('state' , PaymentLog::TERMINADO);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                $_userId = $user->id;
                $_paymentProductOrderPoints = array_filter( $paymentProductOrderPoints->toArray() ,
                    function($p)use($_userId) { return $p['user_id'] == $_userId; }
                );

                $calculatorPoint = $this->calculator->points( $user->uuid , $paymentOrderPoints , $_paymentProductOrderPoints);

                $uuid = $user->uuid;
                $_paymentOrderPoints = array_filter( $paymentOrderPoints->toArray() ,
                    function($p)use($uuid) { return strtoupper($p['sponsor_code']) == strtoupper($uuid) && $p['state'] == true && $p['payment'] == true && $p['type'] != PaymentOrderPoint::GRUPAL; }
                );

                $totalPoints = $calculatorPoint->patrocinio + $calculatorPoint->residual + $calculatorPoint->compra + $calculatorPoint->pointGroup + $calculatorPoint->personal;
                $rangeCurrent = null;
                foreach ($ranges as $key => $range) {
                    if( $range->point <= $totalPoints && $range->childs == count( $_paymentOrderPoints )){
                        $rangeCurrent = $range;
                        break;
                    }
                }

                array_push( $_userList , (object) array(
                    "estado"                => $payment == null ? "" : ( $payment->state == PaymentLog::PAGADO ? "Activo":"Desactivo" ) ,
                    "nombres"               => $user->name,
                    "codigo"                => $user->uuid,
                    "plan"                  => $payment == null ? "Sin plan" : ( $payment->paymentOrder->pack->title ),
                    "bono_personal"         => $calculatorPoint->personal,
                    "bono_pratocinio"       => $calculatorPoint->patrocinio,
                    "bono_residual"         => $calculatorPoint->residual,
                    "bono_totales"          => $calculatorPoint->patrocinio + $calculatorPoint->residual,
                    "punto_grupales"        => $calculatorPoint->pointGroup,
                    "punto_plan_actual"     => $calculatorPoint->compra,
                    "punto_plan_actual"     => $calculatorPoint->compra,
                    "gran_total"            => $totalPoints,
                    "rango"                 => $rangeCurrent == null ? "" : $rangeCurrent->title,
                    "count_rango"           => "0",
                ));
            }

            $attachment = Excel::raw(
                new UsersPointExport( $_userList  ),
                BaseExcel::XLSX
            );
            // $subject = "Purchase Order";
            
            $mailData = [
                'customer_name' => "Edwin",
                'month' => "Febrero",
                'attach'    => $attachment
            ];

            Mail::to( "bossun258@gmail.com" )->send(new UsersPointExcel($mailData));

            return $this->sendResponse( $_userList, 'Exportar');

        } catch (\Throwable $th) {

            return $this->sendError( $th->getMessage());
        }
    }

    public function deleteAllPaymentByUser(Request $request)
    {
        try {

            $userId = Auth::id();

            $user = User::where( "id" , $userId )->first();

            PaymentLog::where("state" , PaymentLog::PAGADO )->where("user_id", $user->id)->delete();

            PaymentOrderPoint::where("state" , true)->where("user_code" , $user->uuid)->delete();

            return $this->sendResponse( "Eliminado Usuario" , 'Confirm');

        }catch (Exception $e){
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function changeSponsor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
            'sponsorCode' => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {

            $userId = Auth::id();

            $dataBody = (object) $request->all();

            $userSponsor = User::where( "uuid" , 'like', $dataBody->sponsorCode )->first();
            $userCurrent = User::where( "uuid" , 'like', $dataBody->userCode )->first();

            $paymentOrderPoint = PaymentOrderPoint::where("sponsor_code" , $userCurrent->uuid)
                ->where("type" , PaymentOrderPoint::COMPRA)
                ->orderBy('created_at', 'desc')
                ->first();

            if( $paymentOrderPoint != null ) return $this->sendError( "Este usuario tiene invitados debajo de él." );

            DB::beginTransaction();

            $paymentLog = PaymentLog::with(['paymentOrder'])->where( "user_id" ,  $userCurrent->id )
                ->where( function ($query) {
                    $query->where('state' , PaymentLog::PAGADO)
                    ->orWhere('state' , PaymentLog::TERMINADO);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            PaymentOrder::where("id" , $paymentLog->paymentOrder->id)->update(array(
                "sponsor_code" => $dataBody->sponsorCode
            ));

            PaymentOrderPoint::where("user_id" , $userCurrent->id)
                // ->where("payment_order_id" , $paymentLog->paymentOrder->id)
                // ->where("type" , $paymentLog->paymentOrder->id)
                ->update(array("sponsor_code" => $dataBody->sponsorCode));

            DB::commit();
            return $this->sendResponse( 1 , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function resetPoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            DB::beginTransaction();

            $dataBody = (object) $request->all();
            $userId = Auth::id();

            $userCurrent = User::where( "uuid" , $dataBody->userCode )->first();

            $paymentLog = PaymentLog::with(['paymentOrder'])->where( "user_id" ,  $userCurrent->id )
                ->where( function ($query) {
                    $query->where('state' , PaymentLog::PAGADO)
                    ->orWhere('state' , PaymentLog::TERMINADO);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            PaymentLog::where( "user_id" , $userCurrent->id )->update(array( "state" => PaymentLog::RESET ));

            PaymentOrderPoint::where("user_id" , $userCurrent->id)
                ->update( array("state" => false, "type" => PaymentOrderPoint::RESET) );

            PaymentProductOrder::where("user_id" , $userCurrent->id)
                ->update( array( "state" => PaymentProductOrder::TERMINADO ) );

            PaymentProductOrderPoint::where("user_id" , $userCurrent->id)
                ->update( array( "state" => false ) );

            RangeUser::where("user_id" , $userCurrent->id)
                ->update( array( "status" => false ) );

            DB::commit();
            return $this->sendResponse( 1 , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function resetAll(Request $request)
    {
        try {
            PaymentLog::with(['paymentOrder'])->where('state' , PaymentLog::PAGADO)
                ->update(array( "state" => PaymentLog::TERMINADO ));

            PaymentOrderPoint::where('state' , true )->update(array( "state" => false ));
            PaymentProductOrderPoint::where("state" , true)->update(array( "state" => false ));

            PaymentProductOrder::where("state" , PaymentProductOrder::PAGADO)->update(array( "state" => PaymentProductOrder::TERMINADO ));

            RangeUser::where("status", true)->update( array("status" => false) );
            return $this->sendResponse( 1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function resetAllPoint(Request $request)
    {
        try {

            setlocale(LC_TIME, 'es_ES.UTF-8'); // Para funciones de fecha nativas (no estrictamente necesario para Carbon)
            Carbon::setLocale('es');           // Esto es lo importante para Carbon

            DB::beginTransaction();

            $userList = User::with(['range.range'])->get();

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();

            $fechaActual = Carbon::now();

            // Obtener mes y año
            $mes = $fechaActual->translatedFormat('F'); // o 'F' para nombre del mes
            $año = $fechaActual->format('Y');
            $month = $fechaActual->format('m');

            $subject = "Resumen General de puntos y bonos del último mes - Imperio Global";

            foreach ($userList as $key => $user) {
                if( $user->is_admin ){
                    // ==== SOLO PARA EL ADMIN
                    $jsonBody = array();
                    foreach ($userList as $keyTemp => $_user){
                        if( $_user->is_admin ) continue;
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
                                "personalGlobal" => $calculator->personalGlobal
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
                                $json->points?->residual ?? 0,
                                ( ($json->points?->pointAfiliado ?? 0) 
                                    + ($json->points?->patrocinio ?? 0) 
                                    + ($json->points?->residual ?? 0) 
                                    + ( ($json->points?->personal ?? 0) * 0.02 ) 
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
                        'fileAttachment' => $nameFile
                    ));

                }else{
                    // ==== SOLO USUARIOS
                    $user = (object) $user;

                    $user->payment = PaymentLog::with(['paymentOrder.pack' ])->where( "user_id" ,  $user->id )
                        ->where( function ($query) {
                            $query->where('state' , PaymentLog::PAGADO)
                            ->orWhere('state' , PaymentLog::TERMINADO);
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if( $user->payment == null ) continue;

                    $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $user->id)->where("state" , true)->get();

                    $calculator = $this->calculator->points( $user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                    $calculatorTotalPoint = $this->calculator->pointsTotal( $user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                    
                    $jsonBody = array(
                        "email" => $user->email,
                        "range" => $user->range == null ? "Sin Rango" : $user->range->range->title,
                        "pack" => $user->payment?->paymentOrder?->pack?->title ?? "Sin Plan",
                        "status" => $user->payment == null ? "--" : ( $user->payment->state == PaymentLog::PAGADO ? "Activo" : "Inactivo" ),
                        "points" => (object) array(
                            "patrocinio"    => $calculator->patrocinio,
                            "residual"      => $calculator->residual,
                            "compra"        => $calculator->compra,
                            "pointGroup"    => $calculator->pointGroup,
                            "personal"      => $calculator->personal,
                            "infinito"      => $calculator->infinito,
                            "pointAfiliado" => $calculator->pointAfiliado,
                            "personalGlobal" => $calculator->personalGlobal
                        ),
                        "totalPoint" => $calculatorTotalPoint
                    );

                    $userTemp = UserEmailTemp::create(array(
                        'userId' => $user->id,
                        'isAdmin' => $user->is_admin,
                        'status' => UserEmailTemp::PENDIENTE,
                        'email' => $user->email,
                        'subject' => $subject. " ". strtoupper($mes) ."-".$año,
                        'month'=> $month,
                        'year'=> $año,
                        'jsonBody'=> serialize($jsonBody),
                    ));

                }
            }

            PaymentLog::with(['paymentOrder'])->where('state' , PaymentLog::PAGADO)
                ->update(array( "state" => PaymentLog::TERMINADO ));

            PaymentOrderPoint::where('state' , true )->update(array( "state" => false ));
            PaymentProductOrderPoint::where("state" , true)->update(array( "state" => false ));

            RangeUser::where("status", true)->update( array("status" => false) );

            DB::commit();
            return $this->sendResponse( 1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function resetUserToTemp(Request $request)
    {
        try {
            DB::beginTransaction();

            $temps = UserEmailTemp::where("status" , UserEmailTemp::PENDIENTE)->get();
            $countSend = 0;
            foreach ($temps as $key => $temp) {
                $user = User::where("id", $temp->userId)->first();
                if( $countSend > 5 ) break;
                if( $temp->isAdmin ){
                    $fileAttachment = storage_path("app/{$temp->fileAttachment}");
                    $mailData = [
                        'customer_name' => $user->name,
                        "subject" => $temp->subject,
                        'attach'    => $fileAttachment
                    ];
                    Mail::to("bossundeveloper258@gmail.com")->send(new UsersPointExcel($mailData));
                    UserEmailTemp::where("id" , $temp->id)->update(array(
                        "status" => UserEmailTemp::ENVIADO
                    ));
                }else{

                    $body = unserialize($temp->jsonBody);

                    $mailData = [
                        'customer_name' => $user->name,
                        "subject" => $temp->subject,
                        "month" => Carbon::createFromDate(null, $temp->month, null)->locale('es')->monthName,
                        "patrocinio" => $body['points']->patrocinio,
                        "compra" => $body['points']->compra,
                        "total" => $body['totalPoint'],
                        "residual" => $body['points']->residual,
                        "personal" => $body['points']->personal,
                        "afiliado" => $body['points']->personalGlobal,
                        "infinito" => $body['points']->infinito,
                        "range" => $body['range'],
                        "plan" => $body['pack'],
                        "status" => $body['status'],
                    ];

                    Mail::to("bossundeveloper258@gmail.com")->send(new UserPointActive($mailData));

                    UserEmailTemp::where("id" , $temp->id)->update(array(
                        "status" => UserEmailTemp::ENVIADO
                    ));
                    break;
                }
                $countSend ++;
            }

            DB::commit();
            return $this->sendResponse( 1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function desactive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            DB::beginTransaction();

            $dataBody = (object) $request->all();
            $userId = Auth::id();

            $userCurrent = User::where( "uuid" , $dataBody->userCode )->first();

            PaymentLog::where( "user_id" , $userCurrent->id )
                ->where('state' , PaymentLog::PAGADO)
                ->update(array( "state" => PaymentLog::TERMINADO ));

            PaymentOrderPoint::where("user_id" , $userCurrent->id)
                ->where("state" , true)
                ->update( array("state" => false, "type" => PaymentOrderPoint::RESET) );

            PaymentProductOrder::where("user_id" , $userCurrent->id)
                ->update( array( "state" => PaymentProductOrder::TERMINADO ) );

            PaymentProductOrderPoint::where("user_id" , $userCurrent->id)
                ->update( array( "state" => false ) );

            DB::commit();
            return $this->sendResponse( 1 , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function activeResidual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
            'products'               => 'required|array',
            'products.*.product'     => 'required|exists:products,id',
            'products.*.quantity'    => 'required|numeric'
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {

            $user_id = Auth::id();
            DB::beginTransaction();
            $userModel = User::with(['file'])->find($user_id);

            if( !$userModel->is_admin ) return $this->sendError( "No tiene permisos ese usuario" );

            $dataBody = (object) $request->all();

            $userUpdated = User::where("uuid" , $dataBody->userCode)->first();

            if( $userUpdated == null ) return $this->sendError( "No se existe el usuario seleccionado" );

            if( count( $dataBody->products ) == 0 ) return $this->sendError( "No se encuentra productos" );

            $paymentLog = PaymentLog::with(['paymentOrder.pack'])
                ->where("user_id" ,  $userUpdated->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO])
                ->orderBy('created_at', 'desc')
                ->first();

            // if( $paymentLog == null ) return $this->sendError( "No tiene ningun plan para avance residual" );

            // ---

            $productIds = array();

            foreach( $dataBody->products as $key => $product ) {
                $product = (object) $product;
                array_push($productIds , $product->product);
            }

            $productList = Product::with(['discounts'])->whereIn('id' , $productIds)->get();

            $productListCreate = array();

            $totalAmount = 0;
            $totalPoints = 0;
            $discount = 0;

            foreach( $productList as $key => $product ) {
                $keyDetail = array_search( $product->id , array_column($dataBody->products , 'product')  );
                $productDetail = (object) $dataBody->products[$keyDetail];

                if( $paymentLog?->paymentOrder?->pack_id != null ){

                    $discounts = array_filter( $product->discounts , fn($v) => $v->pack_id == $paymentLog?->paymentOrder?->pack_id );
                    if( count($discounts) > 0 ){
                        $totalAmount += ( ($product->price * ( (100 - $discounts[0]->discount) / 100 ) ) *  $productDetail->quantity );
                    }else{
                        $totalAmount +=  ($product->price  *  $productDetail->quantity );
                    }

                    $productPointPack = ProductPointPack::where("product_id" , $product->id )->where("pack_id" , $paymentLog?->paymentOrder?->pack_id)->first();
                    if(  $productPointPack == null ) $totalPoints += 0;
                    else{
                        $totalPoints += $productPointPack->point *  $productDetail->quantity;
                    }
                }else{
                    $totalPoints += 0;
                    $totalAmount += ( $product->price *  $productDetail->quantity );
                }

            }

            if( $paymentLog != null ){
                $packCurrent = Pack::find($paymentLog->paymentOrder->pack_id);
                $discount = floatval( $packCurrent->discount );
            }

            // if( $discount > 0 ){
            //     $totalAmount = $totalAmount * (100 - $discount) / 100;
            // }

            $paymentProductOrder = PaymentProductOrder::create(
                array(
                    'currency'  => 'PEN',
                    'amount'    => $totalAmount,
                    'discount'  => $discount,
                    'points'    => $totalPoints,
                    'user_id'   => $userUpdated->id,
                    'pack_id'   => $paymentLog->paymentOrder->pack_id,
                    'phone'     => "",
                    'address'   => "",
                    'state'     => PaymentProductOrder::PAGADO,
                    'type'      => self::PAYMENT_ADMIN,
                    'token'     => 'NOT_FOUND',
                )
            );

            foreach( $productList as $key => $product ) {
                $keyDetail = array_search( $product->id , array_column($dataBody->products , 'product')  );
                $productDetail = (object) $dataBody->products[$keyDetail];
                $price = $product->price;
                $subtotal = $product->price * $productDetail->quantity;
                $_points = 0;
                $productPointPack = ProductPointPack::where("product_id" , $product->id )->where("pack_id" , $paymentLog?->paymentOrder?->pack_id)->first();
                if(  $productPointPack != null ) $_points = $productPointPack->point *  $productDetail->quantity;

                $discounts = array_filter( $product->discounts , fn($v) => $v->pack_id == $paymentLog?->paymentOrder?->pack_id );
                if( count($discounts) > 0 ){
                    $subtotal = ( ($product->price * ( (100 - $discounts[0]->discount) / 100 ) ) *  $productDetail->quantity );
                    $price = $product->price * ( (100 - $discounts[0]->discount) / 100 );
                }

                array_push(
                    $productListCreate,
                    array(
                        'payment_product_order_id'  => $paymentProductOrder->id,
                        'product_id'                => $product->id,
                        'product_title'             => $product->title,
                        'quantity'                  => $productDetail->quantity,
                        'price'                     => $price,
                        'subtotal'                  => $subtotal,
                        'points'                    => $_points,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    )
                );
            }

            PaymentProductOrderDetail::insert($productListCreate);

            PaymentProductOrderPoint::create(
                array(
                    'payment_product_order_id'  => $paymentProductOrder->id,
                    'user_id'                   => $userUpdated->id,
                    'points'                    => $totalPoints,
                    'state'                     => true
                )
            );

            $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $userUpdated->id)->where("state" , true)->get();

            $personalPoint = 0;
            foreach ($paymentProductOrderPoints as $key => $paymentProductOrderPoint) {
                $personalPoint = $personalPoint + $paymentProductOrderPoint->points;
            }

            $maxPointsProduct = Option::where("option_key" , "max_points_product")->first();

            if( $personalPoint >= floatval($maxPointsProduct->option_value) )
            {
                $__paymentLog = PaymentLog::with(['paymentOrder.pack'])
                ->where("user_id" ,  $userUpdated->id )
                ->whereIn("state" , [PaymentLog::TERMINADO])
                ->orderBy('created_at', 'desc')
                ->first();
                // order
                if( $__paymentLog != null ){
                    $orderId = uniqid( $paymentLog->paymentOrder->pack->title );

                    $_paymentOrder = PaymentOrder::create(
                        array(
                            'currency' => "PEN",
                            'amount' => 0,
                            'sponsor_code' => $paymentLog->paymentOrder->sponsor_code,
                            'pack_id' => $paymentLog->paymentOrder->pack_id,
                            "token" => $orderId
                        )
                    );

                    $this->confirmPoint($_paymentOrder , $userUpdated , $paymentLog->paymentOrder->pack, true);
                    
                    $_paymentLog = PaymentLog::create(
                        array(
                            'payment_order_id' => $_paymentOrder->id,
                            "confirm" => true,
                            'user_id' => $userUpdated->id,
                            "state" => PaymentLog::PAGADO,
                        )
                    );
                }
                
            }

            $this->confirmPointAfiliado( $userUpdated , $totalPoints);

            DB::commit();
            return $this->sendResponse( 1 , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , "dsdsdsdsd" , 402 );
        }
    }

    public function exportPdfFinance(Request $request)
    {
        try {
            // Obtener mes y año

            $fechaActual = Carbon::now();
            $oneMonthAgo = $fechaActual->subMonth();
            $mes = $oneMonthAgo->translatedFormat('F'); // o 'F' para nombre del mes
            $year = $oneMonthAgo->format('Y');
            $month = $oneMonthAgo->format('m');

            $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog', 'userPoint.paymentActive'])
                ->whereRaw('MONTH(created_at) = ?', [$month])->whereRaw('YEAR(created_at) = ?', [$year])
                // ->whereMonth('created_at', $oneMonthAgo->format('m'))->whereYear('created_at', $oneMonthAgo->format('Y'))    
                ->get();

            $patrocinioUserActive = 0;
            $patrocinioUserInactive = 0;

            $residualUserActive = 0;
            $residualUserInactive = 0;

            $infinityUser = 0;

            $totalPoint = 0;

            foreach ($paymentOrderPoints as $key => $paymentOrderPoint) {
                if( $paymentOrderPoint->paymentOrder->paymentLog = PaymentLog::PAGADO ){
                    if( $paymentOrderPoint->type == PaymentOrderPoint::PATROCINIO ) $patrocinioUserActive = $patrocinioUserActive + $paymentOrderPoint->point;
                    else if( $paymentOrderPoint->type == PaymentOrderPoint::RESIDUAL ) $residualUserActive = $residualUserActive + $paymentOrderPoint->point;
                }else if( $paymentOrderPoint->paymentOrder->paymentLog = PaymentLog::TERMINADO ){
                    if( $paymentOrderPoint->type == PaymentOrderPoint::PATROCINIO ) $patrocinioUserInactive = $patrocinioUserInactive + $paymentOrderPoint->point;
                    else if( $paymentOrderPoint->type == PaymentOrderPoint::RESIDUAL ) $residualUserInactive = $residualUserInactive + $paymentOrderPoint->point;
                }

                if( $paymentOrderPoint->type == PaymentOrderPoint::INFINITO ) $infinityUser = $infinityUser + $paymentOrderPoint->point;

                $totalPoint = $totalPoint + $paymentOrderPoint->point;
            }

            $data = array(
                "mes" => $mes,
                "year" => $year,
                "patrocinioUserActive" => $patrocinioUserActive,
                "patrocinioUserInactive" => $patrocinioUserInactive,
                "residualUserActive" => $residualUserActive,
                "residualUserInactive" => $residualUserInactive,
                "infinityUser" => $infinityUser,
                "totalPoint" => $totalPoint
            );

            // Renderizar vista PDF
            $pdf = Pdf::loadView('pdf.finance', $data)->setPaper('a4', 'portrait');;
            $output = $pdf->output();
            $base64 = base64_encode($output);

            $fecha = Carbon::now()->format('YmdHis');
            $nameFile = "finanzas_{$fecha}.pdf";

            return $this->sendResponse( [
                'filename' => $nameFile,
                'mime' => 'application/pdf',
                'base64' => $base64
            ] , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function exportExcelFinance(Request $request)
    {
        try {

            $fechaActual = Carbon::now();

            // Obtener mes y año
            $mes = $fechaActual->translatedFormat('F'); // o 'F' para nombre del mes
            $año = $fechaActual->format('Y');
            $month = $fechaActual->format('m');

            $oneMonthAgo = $fechaActual->subMonth();

            $userAdmin = User::where("is_admin" , true)->first();

            $tempUser = UserEmailTemp::where("userId", $userAdmin->id)
                ->where("month", $oneMonthAgo->format('m'))
                ->where("year", $oneMonthAgo->format('Y'))->first();

            $contentFile = Storage::get($tempUser->fileAttachment);

            if(  $tempUser == null){
                return $this->sendError( "No se encontro ningun dato pasado");
            }

            $fecha = Carbon::now()->format('YmdHis');
            $nameFile = "reporte_usuarios_{$fecha}.xlsx";

            $base64 = base64_encode($contentFile);

            return $this->sendResponse( [
                'filename' => $nameFile,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'base64' => $base64
            ] , '');

            // $userList = User::with(['range.range'])->where("is_admin", false)->get();

            // $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder'])->where('state' , true)->get();
            // $jsonBody = array();
            // foreach ($userList as $keyTemp => $_user){
            //     if( $_user->is_admin ) continue;
            //     $_user = (object) $_user;
            //     $_user->payment = PaymentLog::with(['paymentOrder.pack' ])->where( "user_id" ,  $_user->id )
            //     ->where( function ($query) {
            //         $query->where('state' , PaymentLog::PAGADO)
            //         ->orWhere('state' , PaymentLog::TERMINADO);
            //     })
            //     ->orderBy('created_at', 'desc')
            //     ->first();

            //     $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $_user->id)->where("state" , true)->get();

            //     $calculator = $this->calculator->points( $_user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
            //     $calculatorPoint = $this->calculator->pointsTotal( $_user->uuid , $paymentOrderPoints , $paymentProductOrderPoints );
                
            //     array_push( $jsonBody , (object) array(
            //         "fullname" => $_user->name,
            //         "email" => $_user->email,
            //         "uuid" => $_user->uuid,
            //         "pack" => $_user->payment?->paymentOrder?->pack?->title ?? "Sin Plan",
            //         "status" => $_user->payment == null ? "--" : ( $_user->payment->state == PaymentLog::PAGADO ? "Activo" : "Inactivo" ),
            //         "totalPoint" => $calculatorPoint,
            //         "range" => $_user->range == null ? "Sin Rango" : $_user->range->range->title,
            //         "points" => (object) array(
            //             "patrocinio"    => $calculator->patrocinio,
            //             "residual"      => $calculator->residual,
            //             "compra"        => $calculator->compra,
            //             "pointGroup"    => $calculator->pointGroup,
            //             "personal"      => $calculator->personal,
            //             "infinito"      => $calculator->infinito,
            //             "pointAfiliado" => $calculator->pointAfiliado,
            //             "personalGlobal" => $calculator->personalGlobal
            //         ),
            //     ) );
            // }

            // // crear archivo excel
            // $excelBody = array();

            // foreach ($jsonBody as $key => $json) {
            //     array_push(
            //         $excelBody,
            //         array(
            //             $json->fullname,
            //             $json->uuid,
            //             $json->status,
            //             $json->pack,
            //             $json->points?->pointAfiliado ?? 0,
            //             $json->points?->patrocinio ?? 0,
            //             $json->points?->residual ?? 0,
            //             ( ($json->points?->pointAfiliado ?? 0) 
            //                 + ($json->points?->patrocinio ?? 0) 
            //                 + ($json->points?->residual ?? 0) 
            //                 + ( ($json->points?->personal ?? 0) * 0.02 ) 
            //             ),
            //             $json->points?->compra ?? 0,
            //             $json->points->personal ?? 0,
            //             $json->points->infinito ?? 0,
            //             $json->totalPoint,
            //             $json->range
            //         )
            //     );
            // }

            // // 1. Guardar Excel
            // $fecha = Carbon::now()->format('YmdHis');
            // $nameFile = "reporte_usuarios_{$fecha}.xlsx";
            // $nameFilePath = "exports/".$nameFile;

            // Excel::store(new ReportExcelUsers($excelBody), $nameFilePath , null, \Maatwebsite\Excel\Excel::XLSX);

            // // Leer archivo y codificar en base64
            // $fileContents = Storage::get($nameFilePath);
            // $base64 = base64_encode($fileContents);

            // // Eliminar el archivo después de codificar
            // Storage::delete($nameFilePath);

            // return $this->sendResponse( [
            //     'filename' => $nameFile,
            //     'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //     'base64' => $base64
            // ] , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function exportPdfProfile(Request $request)
    {
        try {
            // Obtener mes y año
            $user_id = Auth::id();
            $fechaActual = Carbon::now();

            $mes = $fechaActual->translatedFormat('F'); // o 'F' para nombre del mes
            $año = $fechaActual->format('Y');
            $month = $fechaActual->format('m');

            $oneMonthAgo = $fechaActual->subMonth();

            // ---------------------------

            $tempUser = UserEmailTemp::where("userId", $user_id)
                ->where("month", $oneMonthAgo->format('m'))
                ->where("year", $oneMonthAgo->format('Y'))->first();

            if(  $tempUser == null){
                return $this->sendError( "No se encontro ningun dato pasado");
            }

            $userModel = User::with(['file','range.range.file', 'paymentActive'])->find($user_id);
            // return $this->sendError( "temp" , $tempUser);

            // $payment = PaymentLog::with(['paymentOrder.pack'])
            //         ->where( "user_id" ,  $user_id )
            //         ->where( function ($query) {
            //             $query->where('state' , PaymentLog::PAGADO)
            //             ->orWhere('state' , PaymentLog::TERMINADO);
            //         })
            //         ->orderBy('created_at', 'desc')
            //         ->first();

            // $uuid = $userModel->uuid;

            // $paymentOrderPoints = PaymentOrderPoint::with(['paymentOrder.paymentLog'])->where('state' , true)->get();
            // $paymentProductOrderPoints = PaymentProductOrderPoint::where("user_id" , $user_id)->where("state" , true)->get();

            // $ranges = Range::where("state" , true)
            //     ->orderBy('points', 'asc')
            //     ->get();

            // $calculatorPoint = $this->calculator->points( $userModel->uuid , $paymentOrderPoints , $paymentProductOrderPoints);
            // $totalPoint = $this->calculator->pointsTotal( $userModel->uuid , $paymentOrderPoints , $paymentProductOrderPoints);        

            // $userModel->payment = $payment;
            // $userModel->podints = $calculatorPoint;

            // ---------------------------

            $_pointTemps = unserialize($tempUser->jsonBody);

            $_pointTemp = array();

            if( $userModel->is_admin ){
                foreach ($_pointTemps as $key => $temp) {
                    if( $temp->email == $userModel->email)
                    {
                        $_pointTemp['points'] = $temp->points;
                        $_pointTemp['totalPoint'] = $temp->totalPoint;
                        $_pointTemp['range'] = $temp->range;
                        $_pointTemp['pack'] = $temp->pack;
                        break;
                    }
                }
            }else{
                $_pointTemp = $_pointTemps;
            }

            $data = array(
                "mes" => $oneMonthAgo->translatedFormat('F'),
                "year" => $oneMonthAgo->format('Y'),
                "code" => $userModel->uuid,
                "fullname" => $userModel->name,
                "address" => $userModel->address,
                "patrocinio" => $_pointTemp['points']->patrocinio,
                "residual" => $_pointTemp['points']->residual,
                "compra"        => $_pointTemp['points']->compra,
                "pointGroup"    => $_pointTemp['points']->pointGroup,
                "personal"      => $_pointTemp['points']->personal,
                "infinito"      =>  $_pointTemp['points']->infinito,
                "pointAfiliado" => $_pointTemp['points']->pointAfiliado,
                "personalGlobal" => $_pointTemp['points']->personalGlobal,

                "totalPoint" => $_pointTemp['totalPoint'],
                "range" => $_pointTemp['range'],
                "plan" => $_pointTemp['pack']
            );

            // Renderizar vista PDF
            $pdf = Pdf::loadView('pdf.userpoint', $data)->setPaper('a4', 'portrait');;
            $output = $pdf->output();
            $base64 = base64_encode($output);

            $fecha = Carbon::now()->format('YmdHis');
            $nameFile = "perfil_{$fecha}.pdf";

            return $this->sendResponse( [
                'filename' => $nameFile,
                'mime' => 'application/pdf',
                'base64' => $base64
            ] , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function cashFlowFilter(Request $request)
    {
        try {
            // Obtener mes y año
            $user_id = Auth::id();
            $fechaActual = Carbon::now();

            $mes = $fechaActual->translatedFormat('F'); // o 'F' para nombre del mes
            $year = $fechaActual->format('Y');
            $month = $fechaActual->format('m');

            if( $request->has('month') ) if( !empty($request->query('month')) ) $month = $request->query('month');
            if( $request->has('year') ) if( !empty($request->query('year')) ) $year = $request->query('year');

            $paymentOrderPoints = PaymentOrderPoint::whereMonth('created_at', $month)->whereYear('created_at', $year)->where("type", PaymentOrderPoint::COMPRA)->get();

            $paymentProductOrderPoints = PaymentOrderPoint::whereMonth('created_at', $month)->whereYear('created_at', $year)->where("type", PaymentOrderPoint::COMPRA)->get();

            $paymentOrders = PaymentLog::with(['paymentOrder'])->whereRaw('MONTH(created_at) = ?', [$month])->whereRaw('YEAR(created_at) = ?', [$year])->where("state", PaymentLog::PAGADO)->get();
            $paymentProductOrders = PaymentProductOrder::whereRaw('MONTH(created_at) = ?', [$month])->whereRaw('YEAR(created_at) = ?', [$year])->where("state", PaymentProductOrder::PAGADO)->get();

            return $this->sendResponse(
                array(
                    "orders" => $paymentOrders,
                    "products" => $paymentProductOrders
                ),
                ""
            );
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function paymentsAll(Request $request)
    {
        try {

            $limit = $this->limit;

            if( $request->has('limit') ) $limit = intval( $request->query('limit') );

            $user_id = Auth::id();

            $userCode = "";
            $userCodeCurrent = null;
            if( $request->has('codeuser') ) if( !empty($request->query('codeuser')) ){
                $userCodeCurrent = User::where("uuid" , $request->query('codeuser'))->first();
                $userCode = $request->query('codeuser');
            }

            $paymentProductOrderList = PaymentProductOrder::with(['fileImage' => function ($query) {
                $query->select('id' , 'path');
            }])->select('id', 'user_id', 'state','created_at' , DB::raw('0 as plan') , 'pack_id' ,'phone' ,'points' , 'discount' , DB::raw("'' as payment_order_id") )->whereIn("state", [PaymentProductOrder::PAGADO , PaymentProductOrder::ENVIADO, PaymentProductOrder::PREORDER ]); // ->with(['user','pack','details']);
            $userNameCurrentIds = array();
            if( $userCodeCurrent != null ){
                
                $paymentProductOrderList = $paymentProductOrderList->where("user_id" , $userCodeCurrent->id);
            }

            if( $request->has('name') ) if( !empty($request->query('name')) ){
                $userNameCurrentIds = User::where("name" , "like" , "%". $request->query('name') . "%")->pluck('id')->toArray();
                $paymentProductOrderList = $paymentProductOrderList->whereIn("user_id" , $userNameCurrentIds);
            }

            $paymentOrders = PaymentLog::with(['fileImage' => function ($query) {
                $query->select('id' , 'path');
            }])->select('id', 'user_id', 'state' , 'created_at' , DB::raw('1 as plan') , DB::raw("'' as pack_id") ,
            DB::raw("'' as phone") , DB::raw("'' as points") , DB::raw("'' as discount") , 'payment_order_id' )->whereIn("state", [PaymentLog::PAGADO, PaymentLog::TERMINADO , PaymentProductOrder::PREORDER]);

            if( $userCodeCurrent != null ){
                $paymentOrders = $paymentOrders->where("user_id" , $userCodeCurrent->id);
            }

            if( $request->has('name') ) if( !empty($request->query('name')) ){
                $userNameCurrentIds = User::where("name" , "like" , "%". $request->query('name') . "%")->pluck('id')->toArray();
                $paymentOrders = $paymentOrders->whereIn("user_id" , $userNameCurrentIds);
            }

            $paymentUnion = $paymentProductOrderList->union($paymentOrders);

            $paymentUnion = $paymentUnion->orderBy('created_at', 'desc')->paginate($limit);

            foreach ($paymentUnion as $key => $payment) {
                $paymentUnion[$key]->user = User::with(['file'])->find($payment->user_id);
                $paymentUnion[$key]->details = PaymentProductOrderDetail::where('payment_product_order_id' , $payment->id )->get();
                if( empty( $payment->pack_id ) ){
                    $payment_order = PaymentOrder::with(['pack'])->find($payment->payment_order_id);
                    $paymentUnion[$key]->pack = $payment_order?->pack;
                }else{
                    $paymentUnion[$key]->pack = Pack::find($payment->pack_id);
                }
            }

            // $userList = $userList

            return $this->sendResponse( new PaginationCollection($paymentUnion), $userNameCurrentIds);

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedLink(Request $request)
    {
        try {
            $userId = Auth::id();
            DB::beginTransaction();
            $dateNow = Carbon::now();

            $userModel = User::with([ 'paymentActive' ])->find($userId);

            $token = (string) Str::uuid();

            $inviteUser = InviteUser::create(array(
                'sponsor_user_id' => $userId,
                'sponsor_user_code' => $userModel->uuid,
                'token' => $token,
                'state' => true,
                'type' => InviteUser::LINK,
                'expired_time' => $dateNow->addHours(2),
            ));
            DB::commit();
            return $this->sendResponse( [
                'code' => $token,
            ] , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'users'               => 'required|array',
            'users.*.code'          => 'required|exists:users,uuid',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            $userId = Auth::id();

            DB::beginTransaction();
            $dateNow = Carbon::now();

            $userModel = User::with([ 'paymentActive' ])->find($userId);

            $dataBody = (object) $request->all();

            $token = (string) Str::uuid();

            $inviteUser = InviteUser::create(array(
                'sponsor_user_id' => $userId,
                'sponsor_user_code' => $userModel->uuid,
                'token' => $token,
                'state' => true,
                'type' => InviteUser::EMAIL,
                'expired_time' => $dateNow->addHours(2),
            ));

            // $expiredList = InviteUser::where('expired_time', '<', $dateNow)->get();
            // foreach ($expiredList as $key => $expired) {
            //     GuestsTokenUser::where('invite_user_id', $expired->id)->update(array("state" => false));
            // }
            InviteUser::where('expired_time', '<', $dateNow)->update(array("state" => false));

            $url = env('APP_URL_FRONT') . '/guest/' . $token;

            foreach( $dataBody->users as $key => $user )
            {
                $user = (object) $user;
                // array_push($usersInvited , $user->code);
                $userInvited = User::where("uuid", $user->code)->first();
                $mailData = [
                    'invited_name' => $userInvited->name,
                    "sponsor_name" => $userModel->name,
                    'url'    => $url
                ];

                // GuestsTokenUser::create(array(
                //     'sponsor_user_code' => $userModel->uuid,
                //     'guest_user_code' => $user->code,
                //     'invite_user_id' => $inviteUser->id,
                //     'state' => true
                // ));

                Mail::to($userInvited->email)->send(new InivitedSponsorUser($mailData));
            }

            DB::commit();
            return $this->sendResponse( 1 , '');

        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'         => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            $dataBody = (object) $request->all();
            DB::beginTransaction();

            $dateNow = Carbon::now();
            $inviteUser = InviteUser::where('token', '=', $dataBody->token)->first();

            if( $inviteUser == null ) return $this->sendResponse( "" , "No existe el codigo de invitación.", false);
            if( $inviteUser->state == false) return $this->sendResponse( "" , "El codigo de invitación esta desabilitado.", false);
            if( $inviteUser->expired_time < $dateNow) return $this->sendResponse( "" , "El codigo de invitación ha expirado.", false);

            DB::commit();
            return $this->sendResponse( $dataBody->token , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'         => 'required',
            "accept"        => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            $userId = Auth::id();
            $userModel = User::with([ 'paymentActive' ])->find($userId);

            DB::beginTransaction();
            $dateNow = Carbon::now();
            $dataBody = (object) $request->all();
            $inviteUser = InviteUser::where('token', '=', $dataBody->token)->first();

            $paymentLog = PaymentLog::where("user_id", $userId)->whereIn("state", [PaymentLog::PAGADO, PaymentLog::TERMINADO])->first();
            if( $paymentLog != null ) return $this->sendResponse( "" , "Este usuario ya tiene un patrocinador.", false);

            if( $inviteUser == null ) return $this->sendResponse( "" , "No existe el codigo de invitación.", false);
            if( $inviteUser->state == false) return $this->sendResponse( "" , "El codigo de invitación esta desabilitado.", false);
            if( $inviteUser->expired_time < $dateNow) return $this->sendResponse( "" , "El codigo de invitación ha expirado.", false);

            GuestsTokenUser::create(array(
                'sponsor_user_code' => $inviteUser->sponsor_user_code,
                'guest_user_code' => $userModel->uuid,
                'invite_user_id' => $inviteUser->id,
                'state' => $dataBody->accept
            ));

            InviteUser::where('token', '=', $dataBody->token)->update(array("state" => false));

            DB::commit();
            return $this->sendResponse( 1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedUserCode(Request $request)
    {
        
        try {
            $userId = Auth::id();
            $userModel = User::with([ 'paymentActive' ])->find($userId);

            DB::beginTransaction();
            $dateNow = Carbon::now();
            $dataBody = (object) $request->all();
            // $inviteUser = InviteUser::where('token', '=', $dataBody->token)->first();

            // if( $inviteUser == null ) return $this->sendResponse( "" , "No existe el codigo de invitación.", false);
            // if( $inviteUser->state == false) return $this->sendResponse( "" , "El codigo de invitación esta desabilitado.", false);
            // if( $inviteUser->expired_time < $dateNow) return $this->sendResponse( "" , "El codigo de invitación ha expirado.", false);

            // GuestsTokenUser::create(array(
            //     'sponsor_user_code' => $inviteUser->sponsor_user_code,
            //     'guest_user_code' => $userModel->uuid,
            //     'invite_user_id' => $inviteUser->id,
            //     'state' => $dataBody->accept
            // ));

            $guestsTokenUser = GuestsTokenUser::where("guest_user_code" , $userModel->uuid)->where("state", true)->first();
            if( $guestsTokenUser == null ){
                return $this->sendResponse("" , "No tiene ningun sponsor invitado" , false);
            }

            DB::commit();
            return $this->sendResponse($guestsTokenUser->sponsor_user_code , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function invitedUserCodeRemove(Request $request)
    {
        try {
            $userId = Auth::id();
            $userModel = User::with([ 'paymentActive' ])->find($userId);

            DB::beginTransaction();
            $dateNow = Carbon::now();
            $dataBody = (object) $request->all();

            GuestsTokenUser::where("guest_user_code" , $userModel->uuid)
                ->where("state", true)
                ->update(array(
                    "state" => false
                ));

            DB::commit();
            return $this->sendResponse(1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function createUser(Request $request)
    {
        try {
            $userId = Auth::id();
            $userModel = User::with([ 'paymentActive' ])->find($userId);

            DB::beginTransaction();
            $dateNow = Carbon::now();
            $dataBody = (object) $request->all();

            $userExists = User::where("email" , $dataBody->email)->first();

            if(  $userExists != null ) return $this->sendError( "Ese correo electronico ya existe" );

            $userExistDni = User::where("uuid" , $dataBody->dni)->first();

            if(  $userExistDni != null ) return $this->sendError( "Este DNI ya existe" );

            $sponsor = User::where("uuid" , $dataBody->sponsor)->first();

            if( $sponsor == null ) return $this->sendError('Codigo de Patronisador no existe.');

            // if( $this->confirmPointService->maxChilds( $dataBody->sponsor ) ) return $this->sendError('Tu patrocinador esta al limite de invitados.');

            $sponsorId = $this->confirmPointService->verifyChildNewSponsor( $dataBody->sponsor );

            $packCurrent = Pack::find($dataBody->plan);

            if( $packCurrent == null ) return $this->sendError( "No se existe el plan seleccionado" );
            
            $orderId = uniqid( $packCurrent->title );

            $userCreated = User::create([
                'name'     => $dataBody->name,
                'email'    => $dataBody->email,
                'uuid'     => $dataBody->dni,
                'password' => bcrypt($dataBody->password)
            ]);

            $codeGenerator = new CodeGenerator();

            $validation = VerificationCodeUser::create([
                'user_id' => $userCreated->id,
                'type'  => 1,
                'code' => $codeGenerator->generate(),
                "state" => true
            ]);
                        
            $_paymentOrder = PaymentOrder::create(
                array(
                    'currency' => "PEN",
                    'amount' => $packCurrent->price,
                    'sponsor_code' => $sponsorId,
                    'pack_id' => $dataBody->plan,
                    "token" => $orderId
                )
            );

            $this->confirmPoint($_paymentOrder , $userCreated , $packCurrent);

            $_paymentLog = PaymentLog::create(
                array(
                    'payment_order_id' => $_paymentOrder->id,
                    "confirm" => true,
                    'user_id' => $userCreated->id,
                    "state" => PaymentLog::PAGADO,
                )
            );

            DB::commit();
            return $this->sendResponse(1 , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function videoImageStory(Request $request)
    {
        try {
            $userId = Auth::id();
            $userModel = User::with([ 'paymentActive' ])->find($userId);

            DB::beginTransaction();
            $dateNow = Carbon::now();
            $dataBody = (object) $request->all();

            $fileId = 0;
            $previewFileId = 0;

            if($request->hasfile('file')) $fileId = $this->fileUpload->upload( $request->file('file') , $this->videoStoryUploadPath);
            if($request->hasfile('preview')) $previewFileId = $this->fileUpload->upload( $request->file('preview') , $this->videoPreviewStoryUploadPath);

            VideoImageStory::create(array(
                'file_id' => $fileId,
                'preview_id' => $previewFileId,
                'user_id' => $userId,
                'name' => $dataBody?->name ?? "",
                'description' => $dataBody?->description ?? "",
                'link' => $dataBody?->link ?? "",
                'is_story' => $dataBody->story == '1' ?  true :  false
            ));

            $fileCreate = File::find($fileId);

            DB::commit();
            return $this->sendResponse( $fileCreate->path , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }


    public function getVideoImageStory(Request $request)
    {
        try { 
            $userId = Auth::id();
            DB::beginTransaction();

            $optionLimit = Option::where("option_key" , $request->query('story') == 1 ? "max_videos" : "max_images" )->first();

            $videoImageStories = VideoImageStory::with(['file','preview'])
                ->where("state", true)
                ->where("is_story" , $request->query('story') == 1 ? true : false )->limit($optionLimit->option_value)->orderBy('created_at', 'desc')->get();

            DB::commit();
            return $this->sendResponse( $videoImageStories , '');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    public function deleteVideoImageStory(Request $request)
    {
        try { 
            $userId = Auth::id();
            DB::beginTransaction();

            $dataBody = (object) $request->all();

            VideoImageStory::where("id" , $dataBody->id)->update(array(
                "state" => false
            ));

            DB::commit();
            return $this->sendResponse( 1 , 'delete');
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    private function confirmPoint( $paymentOrder , $userCurrent , $packCurrent, $reactiveAdmin = false)
    {

        $paymentLogsCount = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->count();

        

        // puntos patrocinio
        $sponsorshipPoint = SponsorshipPoint::where("pack_id" , $paymentOrder->pack_id)->first();
        // puntos residuales
        $residualPoint = ResidualPoint::first();

        if( $paymentLogsCount == 0 ){

            // punto de compra
            if( !$reactiveAdmin ){
                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $userCurrent->uuid,
                    'sponsor_code' => $paymentOrder->sponsor_code,
                    'point' => $packCurrent->points,
                    'payment' => true,
                    'type' => PaymentOrderPoint::COMPRA,
                    'user_id' => $userCurrent->id
                ));
            }
            
            // pago puntos patrocinio
            $level = $sponsorshipPoint->level1;
            $point = floatval($packCurrent->points) * floatval($level) / 100;

            PaymentOrderPoint::create(array(
                'payment_order_id' => $paymentOrder->id,
                'user_code' => $userCurrent->uuid,
                'sponsor_code' => $paymentOrder->sponsor_code,
                'point' => $point,
                'payment' => true,
                'type' => PaymentOrderPoint::PATROCINIO,
                'user_id' => $userCurrent->id
            ));

        }else if( $paymentLogsCount > 0 ){

            $option = Option::where("option_key", 'reactive_point')->first();
            // punto de compra
            if( !$reactiveAdmin ){
                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $userCurrent->uuid,
                    'sponsor_code' => $paymentOrder->sponsor_code,
                    'point' => floatval($option->option_value ?? "200"),
                    'payment' => true,
                    'type' => PaymentOrderPoint::COMPRA,
                    'user_id' => $userCurrent->id
                ));
            }

            // pago puntos residual
            // $level = $residualPoint->level1;
            // $option = Option::where("option_key", 'point_residual')->first();
            // // floatval($packCurrent->points)
            // $point = ( floatval($option->option_value) ) * floatval($level) / 100;
            
            //-- se paso a la opcion de compras
            // PaymentOrderPoint::create(array(
            //     'payment_order_id' => $paymentOrder->id,
            //     'user_code' => $userCurrent->uuid,
            //     'sponsor_code' => $paymentOrder->sponsor_code,
            //     'point' => $point,
            //     'payment' => false,
            //     'type' => PaymentOrderPoint::RESIDUAL,
            //     'user_id' => $userCurrent->id
            // ));
        }

        $_paymentOrderPoints = $this->loopTree( array() , $userCurrent->uuid );

        $sponsorshipPoint = SponsorshipPoint::where("pack_id" , $paymentOrder->pack_id)->first();

        $residualPoint = ResidualPoint::first();

        if( $paymentLogsCount == 0 ){
            foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                $_paymentOrderPoint = (object) $_paymentOrderPoint;
                if( $key == 0 ) continue;
                $key++;
                if( $key > 3 ) break;
                $level = $sponsorshipPoint->{'level'.($key)};
                $point = floatval($packCurrent->points) * floatval($level) / 100;
                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $_paymentOrderPoint->user_code,
                    'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                    'point' => $point,
                    'payment' => false,
                    'type' => PaymentOrderPoint::PATROCINIO,
                    'user_id' => $userCurrent->id
                ));
            }
        }else
        if( $paymentLogsCount > 0 ){
            foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                $_paymentOrderPoint = (object) $_paymentOrderPoint;
                if( $key == 0 ) continue;
                $key++;
                if( $key > 3 ) break;
                // $level = $residualPoint->{'level'.($key)};

                // $option = Option::where("option_key", 'point_residual')->first();
                // $point = floatval($option->option_value) * floatval($level) / 100;

                //-- se paso a la opcion de compras
                // PaymentOrderPoint::create(array(
                //     'payment_order_id' => $paymentOrder->id,
                //     'user_code' => $_paymentOrderPoint->user_code,
                //     'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                //     'point' => $point,
                //     'payment' => false,
                //     'type' => PaymentOrderPoint::RESIDUAL,
                //     'user_id' => $userCurrent->id
                // ));
            }

        }

        if( !$reactiveAdmin ){
            foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                $_paymentOrderPoint = (object) $_paymentOrderPoint;

                $point = $packCurrent->points;
                // if( $paymentLogsCount > 0 ){
                //     $option = Option::where("option_key", 'reactive_point')->first();
                //     $point = floatval($option->option_value);
                // }

                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $_paymentOrderPoint->user_code,
                    'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                    'point' => $point,
                    'payment' => false,
                    'type' => PaymentOrderPoint::GRUPAL,
                    'user_id' => $userCurrent->id
                ));
            }
        }
        
        
    }

    private function confirmPointAfiliado( $userCurrent, $points )
    {
        $paymentLog = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->orderBy('created_at', 'desc')->first();
        if( $paymentLog != null ){

            $paymentLogsCount = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->count();

            if( $paymentLogsCount > 1 ){
                $_paymentOrderPoints = $this->loopTree( array() , $userCurrent->uuid );

                $afiliadosPoint = RangeUser::where("user_id", $userCurrent->id)->where("status", true)->first();
                
                $rangeResidualPoints = ResidualPoint::first();

                if( $afiliadosPoint != null ){
                    $rangeResidualPoints = RangeResidualPoints::where("range_id", $afiliadosPoint->range_id)->first();
                }else{
                    $rangeResidualPoints = RangeResidualPoints::where("range_id", 1)->first();
                }

                foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                    $_paymentOrderPoint = (object) $_paymentOrderPoint;
                    $key++; 

                    PaymentOrderPoint::create(array(
                        'payment_order_id' => $paymentLog->payment_order_id,
                        'user_code' => $_paymentOrderPoint->user_code,
                        'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                        'point' => $points,
                        'payment' => false,
                        'type' => PaymentOrderPoint::GRUPAL,
                        'user_id' => $userCurrent->id
                    ));

                    if( $key > 7 ) continue;

                    $level = $rangeResidualPoints->{'level'.($key)};
                    
                    $point = $points * floatval($level) / 100;

                    // antes PaymentOrderPoint::AFILIADOS
                    $__paymentOrderPoint = PaymentOrderPoint::create(array(
                        'payment_order_id' => $paymentLog->payment_order_id,
                        'user_code' => $_paymentOrderPoint->user_code,
                        'sponsor_code' => $_paymentOrderPoint->sponsor_code,
                        'point' => $point,
                        'payment' => false,
                        'type' => PaymentOrderPoint::RESIDUAL,
                        'user_id' => $userCurrent->id
                    ));

                    GeneratonialResidualPoints::create(array(
                        'user_id' => $userCurrent->id,
                        'range_id' => $afiliadosPoint?->range_id ?? 0,
                        'point_id' => $__paymentOrderPoint->id,
                        'points'    => $points,
                        'level' => $key
                    ));
                    
                }
                
            }
            
        }
    }

    public function treeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userCode' => 'required',
        ]);

        if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

        try {
            $user_id = Auth::id();

            $userModel = User::with(['file'])->find($user_id);

            if( !$userModel->is_admin ) return $this->sendError( "No tiene permisos ese usuario" );

            $dataBody = (object) $request->all();

            $userUpdated = User::where("uuid" , $dataBody->userCode)->first();

            $list = $this->loopTree(array(), $dataBody->userCode);

            return $this->sendResponse( $list , '');
        }catch (Exception $e){

            return $this->sendError( $e->getMessage() , [] , 402 );
        }
    }

    private function loopTree( array $a_paymentOrderPoint , string $userCode )
    {
        $paymentOrderPoint = PaymentOrderPoint::select('user_code', 'sponsor_code')
            ->distinct()
            ->where("user_code" , 'like', $userCode)
            ->whereIn("type", [ PaymentOrderPoint::PATROCINIO ])
            ->where("payment" , 1)
            ->first();

        if( $paymentOrderPoint != null ){
            array_push( $a_paymentOrderPoint , $paymentOrderPoint  );

            $a_paymentOrderPoint = $this->loopTree( $a_paymentOrderPoint , $paymentOrderPoint->sponsor_code );

        }

        return $a_paymentOrderPoint;
    }

}
