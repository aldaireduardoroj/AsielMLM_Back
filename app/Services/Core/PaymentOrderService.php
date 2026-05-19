<?php
namespace App\Services\Core;

use App\Models\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\PaymentLog;
use App\Models\SponsorshipPoint;
use App\Models\Pack;
use App\Models\PaymentOrder;
use App\Models\PaymentOrderPoint;
use App\Models\Range;
use App\Models\RangeUser;
use App\Models\Option;
use App\Models\Product;
use App\Models\RangeResidualPoints;
use App\Models\GeneratonialResidualPoints;
use App\Models\PaymentProductOrder;
use App\Models\PaymentProductOrderDetail;

class PaymentOrderService{

    public function __construct()
    {

    }

    public function confirmPoint( $paymentOrder , $userCurrent , $packCurrent, $reactiveAdmin = false)
    {

        $paymentLogsCount = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->count();

        // puntos patrocinio
        $sponsorshipPoint = SponsorshipPoint::where("pack_id" , $paymentOrder->pack_id)->first();

        $optPackInitFast = Option::where("option_key", "bono_init_fast")->first();

        $optPointInitFast = Option::where("option_key", "bono_init_fast_point")->first();
        // puntos residuales

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

            
            if($paymentOrder->pack_id == ($optPackInitFast?->option_value ?? "")){
                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $userCurrent->uuid,
                    'sponsor_code' => $paymentOrder->sponsor_code,
                    'point' => $optPointInitFast?->option_value ?? 50 ,
                    'payment' => true,
                    'type' => PaymentOrderPoint::INIT_FAST,
                    'user_id' => $userCurrent->id
                ));
            }else{
                PaymentOrderPoint::create(array(
                    'payment_order_id' => $paymentOrder->id,
                    'user_code' => $userCurrent->uuid,
                    'sponsor_code' => $paymentOrder->sponsor_code,
                    'point' => $point,
                    'payment' => true,
                    'type' => PaymentOrderPoint::PATROCINIO,
                    'user_id' => $userCurrent->id
                ));
            }
            

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

        }

        $_paymentOrderPoints = $this->loopTree( array() , $userCurrent->uuid );

        $sponsorshipPoint = SponsorshipPoint::where("pack_id" , $paymentOrder->pack_id)->first();

        if( $paymentLogsCount == 0 ){
            if($paymentOrder->pack_id != ($optPackInitFast?->option_value ?? "")){
                foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                    $_paymentOrderPoint = (object) $_paymentOrderPoint;
                    if( $key == 0 ) continue;
                    $key++;
                    if( $key > 5 ) break;
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
            }
            
        }else


        if( !$reactiveAdmin ){
            foreach ($_paymentOrderPoints as $key => $_paymentOrderPoint) {
                $_paymentOrderPoint = (object) $_paymentOrderPoint;

                $point = $packCurrent->points;

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

    public function confirmPointAfiliado( $userCurrent, $points )
    {
        $paymentLog = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->orderBy('created_at', 'desc')->first();

        $optPackInitFast = Option::where("option_key", "bono_init_fast")->first();

        if( $paymentLog != null ){

            $paymentLogsCount = PaymentLog::where( "user_id" , $userCurrent->id )
                ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->count();

            if( $paymentLogsCount > 0 ){
                $_paymentOrderPoints = $this->loopTree( array() , $userCurrent->uuid );

                $afiliadosPoint = RangeUser::where("user_id", $userCurrent->id)->where("status", true)->first();

                $rangeResidualPoints = null;

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

                    if( $key > 9 ) continue;

                    $level = $rangeResidualPoints->{'level'.($key)};

                    if( $level == 0 ) continue;

                    $point = $points * floatval($level) / 100;

                    $userGivePoint = User::where("uuid", $_paymentOrderPoint->sponsor_code)->first();

                    $paymentLogGive = PaymentLog::where( "user_id" , $userGivePoint->id )
                        ->whereIn("state" , [PaymentLog::TERMINADO, PaymentLog::PAGADO] )->orderBy('created_at', 'desc')->first();

                    if( $paymentLogGive != null ){
                        $paymentOrderGive = PaymentOrder::where("id", $paymentLogGive->payment_order_id)->first();
                        if($paymentOrderGive->pack_id == ($optPackInitFast?->option_value ?? "")) continue;
                    }

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

    public function totalProductPatrocinio( $cartList, $userId, $paymentOrder, $preOrder = true, $fileId = null )
    {
        $totalAmount = 0;

        $paymentProductOrder = PaymentProductOrder::create(
            array(
                'currency'  => PaymentOrder::CURRENCY,
                'amount'    => 0,
                'discount'  => 0,
                'points'    => 0,
                'user_id'   => $userId,
                'pack_id'   => $paymentOrder->pack_id,
                'phone'     => "",
                'address'   => "",
                'state'     => $preOrder ?PaymentProductOrder::PAGADO : PaymentProductOrder::PREORDERPAGADO,
                'type'      => 1,
                'token'     => 'NOT_FOUND',
                'file'      => $fileId
            )
        );

        $productListCreate = array();

        $productIds = array();

        foreach( $cartList as $key => $product ) {
            $product = (object) $product;
            array_push($productIds , $product->id);
        }

        $productList = Product::with(['discounts'])->whereIn('id' , $productIds)->get();

        foreach( $productList as $key => $product )
        {
            $product = (object) $product;

            $keyDetail = array_search( $product->id , array_column($cartList , 'id')  );
            $productDetail = (object) $cartList[$keyDetail];

            array_push(
                $productListCreate,
                array(
                    'payment_product_order_id'  => $paymentProductOrder->id,
                    'product_id'                => $product->id,
                    'product_title'             => $product->title,
                    'quantity'                  => $productDetail->quantity,
                    'price'                     => 0,
                    'subtotal'                  => 0,
                    'points'                    => 0,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                )
            );

            $totalAmount +=  ($product->price  *  $productDetail->quantity );
        }

        PaymentProductOrderDetail::insert($productListCreate);

        PaymentOrder::where("id", $paymentOrder->id)->update(
            array(
                'amount' => $totalAmount
            )
        );

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
