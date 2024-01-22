<?php

use Carbon\Carbon;
use App\Models\OldMMIS\Branch;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\MMIS\inventory\Delivery;
use App\Http\Controllers\AuthController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\inventory\StockTransfer;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Http\Controllers\UserManager\UserManagerController;
use App\Http\Controllers\BuildFile\ItemandServicesController;

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

Route::get('/print-purchase-order/{id}', function ($id) {
    $purchase_order = purchaseOrderMaster::with(['administrator', 'comptroller', 'corporateAdmin', 'purchaseRequest.user', 'branch', 'vendor', 'details' => function ($q) {
        $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas.canvaser');
    }])->findOrfail($id);
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-purchase-order/' . $id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $qrData = base64_encode($qrCode);
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;
    $total_amount = 0;

    
    foreach ($purchase_order['details'] as $key => $detail) {
        $total_amount += $detail['purchaseRequestDetail']['recommendedCanvas']['canvas_item_net_amount'];
    }
    
    if($purchase_order->id == 6948){
        $sortKeys = [8805,8798,8803,8804,8800,8801,8796,8797,8802,8795,8799];
        $temp=[];
        foreach ($sortKeys as $sortkey) {
            foreach ($purchase_order['details'] as $detail) {
                if($sortkey == $detail['id']){
                    $temp[] = $detail;
                }
            }
        }
        $purchase_order['details'] = $temp;
    }

    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'purchase_order' => $purchase_order,
        'transaction_date' => Carbon::parse($purchase_order->po_Document_transaction_date)->format('Y-m-d'),
        'canvaser' =>  $purchase_order['details'][0]['purchaseRequestDetail']['recommendedCanvas']['canvaser']['name'],
        'total_amount' => $total_amount
    ];
    $pdf = PDF::loadView('pdf_layout.purchaser_order', ['pdf_data' => $pdf_data]);
    $viewers = explode(',' , $purchase_order->viewers);
    $is_viewer = false;
    if(sizeof($viewers)){
        foreach ($viewers as $viewer) {
            if($viewer == Request()->id){
                $is_viewer = true;
            }
        }
    }
    if($is_viewer == false){
        array_push($viewers, Request()->id);
        $purchase_order->update([
            'viewers' => implode(',', $viewers)
        ]);
    }

    return $pdf->stream('PO-' . $purchase_order['vendor']['vendor_Name'] . '-' . Carbon::now()->format('m-d-Y') . '-' . $purchase_order['po_Document_number'] . '.pdf');
});

Route::get('/print-purchase-request/{id}', function ($id) {
    $purchase_request = PurchaseRequest::with(['warehouse', 'administrator', 'category', 'itemGroup', 'branch', 'user', 'purchaseRequestDetails' => function ($q) {
        $q->with('itemMaster', 'unit', 'unit2');
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

    return $pdf->stream('Purchase order-' . $id . '.pdf');
});

Route::get('/print-stock-transfer/{id}', function ($id) {
    $stock_transfer = StockTransfer::with('delivery.branch', 'purchaseRequest', 'purchaseOrder', 'warehouseSender', 'warehouseReceiver', 'tranferBy', 'receivedBy')->findOrfail($id);
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-stock-transfer/' . $id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $qrData = base64_encode($qrCode);
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'stock_transfer' => $stock_transfer,
        'transaction_date' => Carbon::parse($stock_transfer->created_at)->format('Y-m-d'),
        'delivery_date' => Carbon::parse($stock_transfer['delivery']['created_at'])->format('Y-m-d')
    ];
    $pdf = PDF::loadView('pdf_layout.stock_transfer', ['pdf_data' => $pdf_data]);

    return $pdf->stream('stock_transfer-' . $id . '.pdf');
});

Route::get('/print-delivery/{id}', function ($id) {
    $delivery = Delivery::with(['branch', 'vendor', 'receiver', 'purchaseOrder.purchaseRequest', 'items' => function ($q) {
        $q->with('item', 'unit');
    }])->findOrfail($id);
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-delivery/' . $id);
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $qrData = base64_encode($qrCode);
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'delivery' => $delivery,
        'transaction_date' => Carbon::parse($delivery->rr_Document_Invoice_Date)->format('Y-m-d'),
        'po_date' => Carbon::parse($delivery['purchaseOrder']['po_Document_transaction_date'])->format('Y-m-d')
    ];
    $pdf = PDF::loadView('pdf_layout.delivery', ['pdf_data' => $pdf_data]);

    return $pdf->stream('delivery-' . $id . '.pdf');
});

Route::get('test-pdf', function () {
    $po_items = PurchaseOrderDetails::with('item', 'purchaseOrder.purchaseRequest', 'purchaseRequestDetail.recommendedCanvas.vendor')
        ->whereHas('purchaseOrder', function ($q1) {
            $q1->where('po_Document_branch_id', 1)->where('po_Document_warehouse_id', 47)->whereDoesntHave('delivery');
        })->get();
    $branch = Branch::find(1);
    $warehouse = Warehouses::find(1);
    $pdf_data = [
        'items' => $po_items,
        'branch_name' => $branch->companyname,
        'warehouse_name' => $warehouse->warehouse_description,
    ];
    $pdf = PDF::loadView('reports.undeliveredPO', ['pdf_data' => $pdf_data]);

    return $pdf->stream('Purchase order-' . '.pdf');
});

Route::group(['middleware' => 'admin.user'], function () {
    require_once('mmis/mmismainroute.php');
    Route::get('user-details', [AuthController::class, 'userDetails']);
    // Route::get('/{any}', function () {
    //     return view('layouts.main');
    // })->where('any', '.*');
});
// Route::group(['middleware' => 'admin.user'], function () {
//     // require_once ('mmis/mmismainroute.php');
//     // Route::get('user-details', [AuthController::class, 'userDetails']);
//     // Route::get('/{any}', function () {
//     //     return view('layouts.main');
//     // })->where('any', '.*');
// });
