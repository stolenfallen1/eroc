<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Http\Controllers\UserManager\UserManagerController;
use App\Http\Controllers\BuildFile\ItemandServicesController;
use App\Models\MMIS\inventory\Delivery;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('login', ['uses' => 'TCG\\Voyager\\Http\\Controllers\\VoyagerAuthController@login',     'as' => 'login']);

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

Route::get('/print-purchase-order/{id}', function ($id){
    $purchase_order = purchaseOrderMaster::with(['administrator', 'comptroller', 'purchaseRequest.user', 'branch', 'vendor', 'details' => function($q){
        $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas');
    }])->findOrfail($id);
    $qrCode = QrCode::size(200)->generate(config('app.url').'/print-purchase-order/'.$id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $qrData = base64_encode($qrCode);
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'purchase_order' => $purchase_order,
        'transaction_date' => Carbon::parse($purchase_order->po_Document_transaction_date)->format('Y-m-d')
    ];
    $pdf = PDF::loadView('pdf_layout.purchaser_order', ['pdf_data' => $pdf_data]);

    return $pdf->stream('Purchase order-'.$id.'.pdf');
});

Route::get('/print-purchase-request/{id}', function ($id){
    $purchase_request = PurchaseRequest::with(['warehouse', 'administrator', 'category', 'itemGroup', 'branch', 'user', 'purchaseRequestDetails' => function($q){
        $q->with('itemMaster', 'unit');
    }])->findOrfail($id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $pdf_data = [
        'logo' => $imageSrc,
        'purchase_request' => $purchase_request,
        'requested_date' => Carbon::parse($purchase_request->pr_Transaction_Date)->format('Y-m-d'),
        'Required_date' => Carbon::parse($purchase_request->pr_Transaction_Date_Required)->format('Y-m-d')
    ];
    $pdf = PDF::loadView('pdf_layout.purchaser_request', ['pdf_data' => $pdf_data]);

    return $pdf->stream('Purchase order-'.$id.'.pdf');
});

Route::get('/print-delivery/{id}', function ($id){
    $delivery = Delivery::with(['branch', 'vendor', 'receiver', 'purchaseOrder.purchaseRequest', 'items'=>function($q){
        $q->with('item', 'unit');
    }])->findOrfail($id);
    $qrCode = QrCode::size(200)->generate(config('app.url').'/print-delivery/'.$id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $qrData = base64_encode($qrCode);
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'delivery' => $delivery,
        'transaction_date' => Carbon::parse($delivery->rr_Document_Transaction_Date)->format('Y-m-d'),
        'po_date' => Carbon::parse($delivery['purchaseOrder']['po_Document_transaction_date'])->format('Y-m-d')
    ];
    $pdf = PDF::loadView('pdf_layout.delivery', ['pdf_data' => $pdf_data]);

    return $pdf->stream('delivery-'.$id.'.pdf');
});

Route::group(['middleware' => 'admin.user'], function () {
    require_once ('mmis/mmismainroute.php');
    Route::get('user-details', [AuthController::class, 'userDetails']);
    Route::get('/{any}', function () {
        return view('layouts.main');
    })->where('any', '.*');
});

