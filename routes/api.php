<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PackController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentOrderController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\PaymentOrderPointController;
use App\Http\Controllers\Api\IzipayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PaymentProductOrderController;
use App\Http\Controllers\Api\RangeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {

    Route::post('login', [LoginController::class, 'login']);
    Route::post('reset-password', [LoginController::class, 'resetPassword']);

    Route::post('auth/register', [LoginController::class, 'register']);
    Route::get('option/search', [OptionController::class, 'search']);

    Route::post('auth/recover-password', [LoginController::class, 'recover']);
    Route::post('auth/validate-code/{uuid}', [LoginController::class, 'validate']);

    Route::post('payment/flow/confirm/{uuid}', [PaymentOrderController::class, 'flowConfirm']);

    Route::get('payment/reset/{code}', [PaymentOrderController::class, 'cancelAllPaymentByUser']);
    Route::get('payment/reset', [PaymentOrderController::class, 'cancelAllPayment']);
    Route::get('payment/delete/{code}', [PaymentOrderController::class, 'deleteAllPaymentByUser']);

    Route::post('payment/izipay/ipn', [IzipayController::class, 'notificationIpn']);

    Route::get('auth/export', [UserController::class, 'export']);


    Route::middleware('auth:api')->group(function () {
        Route::get('auth', [UserController::class, 'auth']);

        Route::post('auth/update/avatar', [UserController::class, 'authUpdateAvatar']);
        Route::put('auth/update', [UserController::class, 'authUpdate']);
        Route::get('auth/search', [UserController::class, 'search']);

        Route::post('payment/flow', [PaymentOrderController::class, 'flowCreate']);
        Route::post('payment/flow/create-offline', [PaymentOrderController::class, 'flowCreateOffline']);
        Route::post('payment/flow/confirm-offline/{uuid}', [PaymentOrderController::class, 'flowCreateConfirmOffline']);

        Route::get('points/list', [PaymentOrderPointController::class, 'points']);
        Route::get('points/users', [PaymentOrderPointController::class, 'pointsUser']);

        Route::post('pack', [PackController::class, 'register']);
        Route::post('pack/update/{packId}', [PackController::class, 'update']);

        Route::post('pack/residual', [PackController::class, 'residual']);
        Route::post('pack/patrocinio', [PackController::class, 'patrocinio']);
        Route::post('pack/state/{packId}', [PackController::class, 'changeStatus']);

        // ********** OptionController
        Route::get('option/search', [OptionController::class, 'search']);
        Route::post('option/truncate', [OptionController::class, 'truncate']);
        Route::post('option/create', [OptionController::class, 'create']);
        Route::post('option/reboot', [OptionController::class, 'reboot']);

        Route::post('payment/izipay/create', [PaymentOrderController::class, 'createPaymentIzipay']);
        Route::post('payment/izipay/confirm', [PaymentOrderController::class, 'confirmPaymentIzipay']);

        Route::get('users/find-all', [UserController::class, 'findAll']);
        Route::post('users/modify', [UserController::class, 'modifyUser']);
        Route::post('users/change-sponsor', [UserController::class, 'changeSponsor']);
        Route::post('users/reset', [UserController::class, 'resetPoint']);
        Route::post('users/reset-all', [UserController::class, 'resetAll']);
        Route::post('users/reset-all-points', [UserController::class, 'resetAllPoint']);
        Route::post('users/desactive', [UserController::class, 'desactive']);
        Route::post('users/active-residual', [UserController::class, 'activeResidual']);
        Route::post('users/list-tree', [UserController::class, 'treeList']);
        Route::post('users/reset-send-email', [UserController::class, 'resetUserToTemp']);
        Route::post('users/pdf-finance', [UserController::class, 'exportPdfFinance']);
        Route::post('users/excel-finance', [UserController::class, 'exportExcelFinance']);

        Route::post('users/excel-finance', [UserController::class, 'exportExcelFinance']);
        Route::get('users/cash-flow', [UserController::class, 'cashFlowFilter']);
        Route::get('users/payments/find-all', [UserController::class, 'paymentsAll']);

        Route::post('users/pdf-profile', [UserController::class, 'exportPdfProfile']);

        Route::post('payment/cash-pre', [PaymentOrderController::class, 'paymentCash']);
        Route::post('payment/cash-confirm', [PaymentOrderController::class, 'paymentCashConfirm']);

        // inivted
        Route::post('users/generate-invited', [UserController::class, 'invitedLink']);
        Route::post('users/invited-email', [UserController::class, 'invitedLinkEmail']);
        Route::post('users/invited-confirm', [UserController::class, 'invitedConfirm']);
        Route::get('users/invited-user', [UserController::class, 'invitedUserCode']);
        Route::post('users/invited-remove', [UserController::class, 'invitedUserCodeRemove']);

        // ********** ProductController
        Route::post('product', [ProductController::class, 'register']);
        Route::get('product/search', [ProductController::class, 'search']);
        Route::post('product/update/{productId}', [ProductController::class, 'update']);
        Route::post('product/points', [ProductController::class, 'points']);
        Route::get('product/points/search', [ProductController::class, 'pointsSearch']);

        Route::post('product/payment/offline', [PaymentProductOrderController::class, 'paymentOffline']);
        Route::post('product/payment/offline-confirm', [PaymentProductOrderController::class, 'paymentOfflineConfirm']);

        // ********** PaymentProductOrderController
        Route::get('product/payment/find-all', [PaymentProductOrderController::class, 'findAll']);
        Route::get('product/payment/search', [PaymentProductOrderController::class, 'search']);
        Route::get('product/payment/points', [PaymentProductOrderController::class, 'points']);
        Route::post('product/payment/flow', [PaymentProductOrderController::class, 'flowCreate']);
        Route::post('product/payment/izipay-create', [PaymentProductOrderController::class, 'izipayCreate']);
        Route::post('product/payment/izipay-confirm', [PaymentProductOrderController::class, 'izipayConfirmPayment']);
        Route::post('product/payment/change-state', [PaymentProductOrderController::class, 'changeState']);

        // ********** RangeController
        Route::post('range', [RangeController::class, 'register']);
        Route::get('range/search', [RangeController::class, 'list']);
        Route::post('range/update/{id}', [RangeController::class, 'update']);
        Route::post('range/users', [RangeController::class, 'users']);
        Route::post('range/user/{userCode}', [RangeController::class, 'usersByCode']);

        // ********** PaymentOrder
        Route::post('payment/offline', [PaymentOrderController::class, 'paymentOffline']);
    });

    Route::post('users/invited-verify', [UserController::class, 'invitedVerify']);
    

    Route::get('pack/search', [PackController::class, 'search']);


});
