<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use App\Models\MMIS\inventory\Consignment;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\inventory\VwDeliveryMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\QuotationMaster;
use App\Models\MMIS\inventory\StockTransferMaster;
use App\Models\MMIS\inventory\VwConsignmentMaster;
use App\Models\MMIS\inventory\VwConsignmentDelivery;
use App\Models\MMIS\inventory\VwPurchaseOrderMaster;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;
use App\Http\Controllers\MMIS\PriceList\PriceListController;
use App\Http\Controllers\MMIS\Reports\PurchaseSubsidiaryReportController;


// <!====================================== PURCHASE ORDER  ====================================== !> 

Route::get('/print-purchase-order/{id}', function ($pid) {

    $id = Crypt::decrypt($pid);
    try {
         $purchase_order = VwPurchaseOrderMaster::with(['items' => function ($query) {
            $query->orderBy('prdetailsid', 'asc');
        }])->where('id',$id)->first();
        $po = purchaseOrderMaster::where('id',$id)->first();
        $consignment = VwConsignmentMaster::where('po_Document_Number',$po->po_Document_number)->first();
        // Generate the QR code for the purchase-order
        $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-purchase-order/' . $id);

        // Load the logo image and encode it in base64
        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        // Encode the QR code in base64
        $qrData = base64_encode($qrCode);
        $qrSrc = 'data:image/jpeg;base64,' . $qrData;
        // 12% VAT rate and a discount (adjust as needed)
        $vatAmount = 0;
        $discount = 0;
        $Nondiscount = 0;
        $subTotalNonFreeGoods = 0;
        $grandTotalNonFreeGoods = 0;
        $currency = '₱';
        purchaseOrderMaster::where('id',$id)->update([
            'viewers' => $purchase_order->po_Document_userid,
            'isprinted'=>1
        ]);
        // Filter items where isfreegood = 1 and isfreegood = 0
        $freeGoods = $purchase_order->items->filter(function ($item) {
            return $item->isFreeGoods == 1;
        });

        $nonFreeGoods = $purchase_order->items->filter(function ($item) {
            return $item->isFreeGoods == 0;
        });



        // Calculate totals for non-free goods
        foreach ($nonFreeGoods as $item) {
            $itemTotal = $item->order_qty * $item->price;
            $subTotalNonFreeGoods += (float)$itemTotal;
           
            $Nondiscount += (float)$item->discount;
            $vatAmount += (float)$item->vat_amount;
            $grandTotalNonFreeGoods += (float)$item->net_amount;
            if ($item->currency_id == 2) {
                $currency = '$';
            }
        }
        // Calculate overall total
        // Prepare the data for the PDF
        $pdf_data = [
            'logo' => $imageSrc,
            'qr' => $qrSrc,
            'purchase_order' => $purchase_order,
            'purchase_order_items' => $nonFreeGoods,
            'free_goods_purchase_order_items' => $freeGoods,
            'sub_total' => $subTotalNonFreeGoods,
            'discount' => $Nondiscount,
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotalNonFreeGoods,
            'consignment'=>$consignment,
            'currency' => $currency,
        ];

        $pdf = PDF::loadView('pdf_layout.purchaser_order', ['pdf_data' => $pdf_data]);
        // Render the PDF
        $pdf->render();

        // Add page numbers to the PDF
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
        $dompdf->get_canvas()->page_text(750, 595, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, [0, 0, 0]);
        return $pdf->stream('PO-' . $purchase_order['vendor_Name'] . '-' . now()->format('m-d-Y') . '-' . $purchase_order['poNumber'] . '.pdf');
    } catch (Exception $e) {
        return $e->getMessage();
    }
    
});

// <!====================================== END PURCHASE ORDER  ====================================== !> 


// <!====================================== PURCHASE REQUEST  ====================================== !> 

Route::get('/print-purchase-request/{id}', function ($prid) {
    $id = Crypt::decrypt($prid);
    try {
        $purchase_request = PurchaseRequest::with(['warehouse', 'administrator', 'consultantApprovedBy', 'category', 'itemGroup', 'branch', 'user', 'purchaseRequestDetails' => function ($q) {
            $q->with('itemMaster', 'unit', 'unit2');
        }])->findOrFail($id);

        if (!$purchase_request) {
            return response()->json(['data' => 'Purchase request not found'], 404);
        }

        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        $imageSrc = 'data:' . $mimeType . ';base64,' . $imageData;

        $pdf_data = [
            'logo' => $imageSrc,
            'purchase_request' => $purchase_request,
            'requested_date' => Carbon::parse($purchase_request->pr_Transaction_Date)->format('Y-m-d'),
            'Required_date' => Carbon::parse($purchase_request->pr_Transaction_Date_Required)->format('Y-m-d')
        ];

        $pdf = PDF::loadView('pdf_layout.purchaser_request', ['pdf_data' => $pdf_data]);

        return $pdf->stream('PR-' . $id . '.pdf');
    } catch (DecryptException $e) {
        Log::error('Decryption error: ' . $e->getMessage());
        return response()->json(['data' => 'No match found'], 200); // Return unencrypted value or handle error
    }
});

// <!====================================== END PURCHASE REQUEST  ====================================== !> 

// <!======================================  REQUEST FOR QUOTATION  ====================================== !> 

Route::get('/print-quotation/{id}', function ($prid) {
    $id = Crypt::decrypt($prid);
    try {
        $rfq_request = QuotationMaster::with(['purchaseRequest','purchaseRequest.warehouse','user','vendor','warehouse','branch','item'])->where('rfq_document_Reference_Number',$id)->get();

        if (!$rfq_request) {
            return response()->json(['data' => 'REQUEST FOR QUOTATION request not found'], 404);
        }

        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        $imageSrc = 'data:' . $mimeType . ';base64,' . $imageData;
        

        $rfqHeader = [
            'pr_number'                         => $rfq_request[0]['purchaseRequest']['code'],
            'rfq_document_Reference_Number'     => $rfq_request[0]['rfq_document_Reference_Number'],
            'rfq_document_Date_Required'        => Carbon::parse($rfq_request[0]['rfq_document_Date_Required'])->format('m/d/Y'),
            'rfq_document_Issued_Date'          => Carbon::parse($rfq_request[0]['rfq_document_Issued_Date'])->format('m/d/Y'),
            'rfq_document_IntructionToBidders'  => $rfq_request[0]['rfq_document_IntructionToBidders'],
            'rfq_document_LeadTime'             => $rfq_request[0]['rfq_document_LeadTime'],
            'rfq_document_Vendor_Id'            => $rfq_request[0]['vendor']['vendor_Name'] ?? "",
            'rfq_document_Vendor_address'       => $rfq_request[0]['vendor']['vendor_Address'] ?? "",
            'rfq_document_Vendor_telno'         => $rfq_request[0]['vendor']['vendor_TelNo'] ?? "",
            'rfq_document_IssuedBy'             => $rfq_request[0]['user']['name'] ?? "",
            'warehouse'                         => $rfq_request[0]['warehouse']['warehouse_description'] ?? "",
            'branch'                            => $rfq_request[0]['branch']['name'] ?? "",
            'address'                           => $rfq_request[0]['branch']['address'] ?? "",
            'TIN'                               => $rfq_request[0]['branch']['TIN'] ?? "",
        ];

        $pdf_data = [
            'logo' => $imageSrc,
            'rfq_header' => $rfqHeader,
            'rfq_request' => $rfq_request,
        ];

        $pdf = PDF::loadView('pdf_layout.rfq', ['pdf_data' => $pdf_data]);

        return $pdf->stream('RFQ-' . $id . '.pdf');
    } catch (DecryptException $e) {
        Log::error('Decryption error: ' . $e->getMessage());
        return response()->json(['data' => 'No match found'], 200); // Return unencrypted value or handle error
    }
});

// <!====================================== END REQUEST FOR QUOTATION  ====================================== !> 



// <!====================================== STOCK TRANSFER  ====================================== !> 

Route::get('/print-stock-transfer/{id}', function ($id) {
    // Fetch the stock transfer details along with related models
    $stock_transfer = StockTransferMaster::with([
        'branch',
        'stockTransferDetails',
        'warehouseSender',
        'warehouseReceiver',
        'tranferBy',
        'receivedBy',
        'status'
    ])->findOrFail($id);

    // Generate the QR code for the stock transfer
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-stock-transfer/' . $id);

    // Load the logo image and encode it in base64
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    // Encode the QR code in base64
    $qrData = base64_encode($qrCode);
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;

    // Prepare the data for the PDF
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'stock_transfer' => $stock_transfer,
        'transaction_date' => Carbon::parse($stock_transfer->created_at)->format('Y-m-d'),
        'delivery_date' => Carbon::parse($stock_transfer->transfer_date)->format('Y-m-d')
    ];

    // Generate the PDF using the prepared data
    $pdf = PDF::loadView('pdf_layout.stock_transfer', ['pdf_data' => $pdf_data]);

    // Stream the generated PDF to the browser
    return $pdf->stream('stock_transfer-' . $id . '.pdf');
});

// <!====================================== END STOCK TRANSFER  ====================================== !> 



// <!====================================== DELIVERY  ====================================== !> 
Route::get('/print-delivery/{id}', function ($pid) {
    // Decrypt the ID from the encrypted parameter
    $id = Crypt::decrypt($pid);
    // Fetch the delivery details along with related models
    $delivery = VwDeliveryMaster::with(['items' => function ($query) {
                            $query->orderBy('pr_detail_id', 'asc');
                        },'warehouse'])->where('id', $id)->first();

    // Generate the QR code for the delivery
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-delivery/' . $id);

    // Load the logo image and encode it in base64
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    // Encode the QR code in base64
    $qrData = base64_encode($qrCode);
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;


    // 12% VAT rate and a discount (adjust as needed)
    $vatAmount = 0; // 12% VAT totalVatAmount
    $totalVatAmount = 0; // 12% VAT totalVatAmount
    $discount = 0; // 10% discount

    // Calculate item totals, discount, VAT, and overall total
    $subTotal = 0;
    $NetTotal = 0;
    $grandTotal = 0;
    $currency = '₱';

    // Filter items where isfreegood = 1 and isfreegood = 0
    $freeGoods = $delivery->items->filter(function ($item) {
        return $item->isFreeGoods == 1;
    });
   
    $groupedFreeGoods = $freeGoods->groupBy('itemname');

    $nonFreeGoods = $delivery->items->filter(function ($item) {
        return $item->isFreeGoods == 0;
    });
    $qty = 0;
    $grand = 0;
    foreach ($nonFreeGoods as $item) {
        
            $itemTotal = $item->served_qty * $item->price; // Modify field names based on your structure
            $subTotal += (float)$itemTotal;
            $discount += (float)$item->discount;
            $vatAmount += (float)$item->vatamount;
            $grandTotal += (float)$item->net_amount;

            if($item->vat_type == 1){
                $grand = $grandTotal + $vatAmount;
            }else{
                
                $grand = $grandTotal - $vatAmount;
            }
            if ($item->currency_id == 2) {
                $currency = '$';
            }
      
    }

    $groupedNonFreeGoods = $nonFreeGoods->groupBy('itemname');
    // Calculate overall total

    // Prepare the data for the PDF
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'delivery' => $delivery,
        'delivery_items' => $nonFreeGoods,
        'groupedFreeGoods' => $groupedFreeGoods,
        'groupedNonFreeGoods' => $groupedNonFreeGoods,
        'free_goods_delivery_items' => $freeGoods,
        'sub_total' => $subTotal,
        'discount' => $discount,
        'vat_amount' => $vatAmount,
        'grand_total' => $grand,
        'currency' => $currency
    ];

    // Generate the PDF using the prepared data
    $pdf = PDF::loadView('pdf_layout.delivery-landscape', ['pdf_data' => $pdf_data])->setPaper('letter', 'portrait');

    // Render the PDF
    $pdf->render();

    // Add page numbers to the PDF
    $dompdf = $pdf->getDomPDF();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
    $dompdf->get_canvas()->page_text(750, 595, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, [0, 0, 0]);

    // Stream the generated PDF to the browser
    return $pdf->stream('delivery-' . $id . '.pdf');
});

// <!====================================== END DELIVERY  ====================================== !> 



// <!====================================== CONSIGNMENT DELIVERY  ====================================== !> 
Route::get('/print-consignment-delivery/{id}', function ($id) {
    // Retrieve the RR ID from the request

    // $id = Crypt::decrypt($pid);
    // Fetch the delivery details along with related models
    $delivery = VwConsignmentDelivery::with('items')->where('id', $id)->first();
    // Generate the QR code for the delivery
    $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-delivery/' . $id);

    // Load the logo image and encode it in base64
    $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    // Encode the QR code in base64
    $qrData = base64_encode($qrCode);
    $qrSrc = 'data:image/jpeg;base64,' . $qrData;


    // 12% VAT rate and a discount (adjust as needed)
    $vatAmount = 0; // 12% VAT
    $discount = 0; // 10% discount

    // Calculate item totals, discount, VAT, and overall total
    $subTotal = 0;
    $NetTotal = 0;
    $grandTotal = 0;
    $currency = '₱';
    foreach ($delivery->items as $item) {
        $itemTotal = $item->served_qty * $item->price; // Modify field names based on your structure
        $subTotal += (float)$itemTotal;
        $discount += (float)$item->discount;
        $vatAmount += (float)$item->vat;
        $grandTotal += (float)$item->net_amount + (float)$item->vat;
        if ($item->currency_id == 2) {
            $currency = '$';
        }
    }
    // Calculate overall total

    // Prepare the data for the PDF
    $pdf_data = [
        'logo' => $imageSrc,
        'qr' => $qrSrc,
        'delivery' => $delivery,
        'sub_total' => $subTotal,
        'discount' => $discount,
        'vat_amount' => $vatAmount,
        'grand_total' => $grandTotal,
        'currency' => $currency
    ];
    // Generate the PDF using the prepared data and set the paper size to letter in landscape
    $pdf = PDF::loadView('pdf_layout.consignment-delivery', ['pdf_data' => $pdf_data])
        ->setPaper('letter', 'portrait');

    // Render the PDF
    $pdf->render();

    // Add page numbers to the PDF
    $dompdf = $pdf->getDomPDF();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
    $dompdf->get_canvas()->page_text(760, 595, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, [0, 0, 0]);

    // Stream the generated PDF to the browser
    return $pdf->stream('purchase-order-consignment-' . $id . '.pdf');
});
// <!====================================== END CONSIGNMENT DELIVERY  ====================================== !> 



// <!====================================== CONSIGNMENT PO  ====================================== !> 
Route::get('/print-purchase-order-consignment', function (Request $request) {

    try {
        // Retrieve the RR ID from the request
        $id = $request->input('rr_id');
        $po_id = $request->input('po_id');
        // $id = Crypt::decrypt($pid);
        // Fetch the delivery details along with related models
        $delivery = VwConsignmentMaster::with('items','warehouse')->where('id', $id)->first();
        // Generate the QR code for the delivery
        $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-delivery/' . $id);

        // Load the logo image and encode it in base64
        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        // Encode the QR code in base64
        $qrData = base64_encode($qrCode);
        $qrSrc = 'data:image/jpeg;base64,' . $qrData;


        // 12% VAT rate and a discount (adjust as needed)
        $vatAmount = 0; // 12% VAT
        $discount = 0; // 10% discount

        // Calculate item totals, discount, VAT, and overall total
        $subTotal = 0;
        $NetTotal = 0;
        $grandTotal = 0;
        $currency = '₱';
        foreach ($delivery->items as $item) {
            $itemTotal = $item->qty * $item->price; // Modify field names based on your structure
            $subTotal += (float)$itemTotal;
            $discount += (float)$item->discount;
            $vatAmount += (float)$item->vat;
            $grandTotal += (float)$item->net_amount;
            if ($item->currency_id == 2) {
                $currency = '$';
            }
        }

        // Calculate overall total

        // Prepare the data for the PDF
        $pdf_data = [
            'logo' => $imageSrc,
            'qr' => $qrSrc,
            'delivery' => $delivery,
            'sub_total' => $subTotal,
            'discount' => $discount,
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotal,
            'currency' => $currency
        ];

        // // Prepare the data for the PDF
        // $pdf_data = [
        //     'logo' => $imageSrc,
        //     'qr' => $qrSrc,
        //     'delivery' => $delivery,
        //     'transaction_date' => Carbon::parse($delivery->rr_Document_Invoice_Date)->format('Y-m-d'),
        //     // Uncomment the next line if purchase order date is needed
        //     // 'po_date' => Carbon::parse($delivery->purchaseOrder->po_Document_transaction_date)->format('Y-m-d')
        // ];

        // Generate the PDF using the prepared data and set the paper size to letter in landscape
        $pdf = PDF::loadView('pdf_layout.purchase-order-consignment', ['pdf_data' => $pdf_data])
            ->setPaper('letter', 'landscape');

        // Render the PDF
        $pdf->render();

        // Add page numbers to the PDF
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
        $dompdf->get_canvas()->page_text(760, 595, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, [0, 0, 0]);

        // Stream the generated PDF to the browser
        return $pdf->stream('purchase-order-consignment-' . $id . '.pdf');
    } catch (Exception $e) {
        return $e->getMessage();
    }
});




Route::controller(PurchaseSubsidiaryReportController::class)->group(function () {
    Route::get('print-all-supplier', 'printAllSupplier');
});

Route::controller(PriceListController::class)->group(function () {
    Route::get('price-all-report', 'printAllLocation');
    Route::get('generate-price-list', 'priceList');  
});



// <!====================================== END CONSIGNMENT PO  ====================================== !> 
