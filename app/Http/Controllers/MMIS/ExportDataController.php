<?php

namespace App\Http\Controllers\MMIS;

use PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OldMMIS\Branch;
use App\Models\BuildFile\Branchs;
use App\Exports\WarehouseItemExport;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MMIS\inventory\Delivery;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class ExportDataController extends Controller
{
    public function exportData(Request $request){
        if($request->type == 1){
           return $this->inventoryManagement($request);
        }elseif($request->type == 2){
            return $this->unprocessedPO($request);
        }elseif($request->type == 3){
            return $this->unprocessedPR($request);
        }elseif($request->type == 4){
            return $this->printInvoice($request);
        }
    }

    private function inventoryManagement($request){
        return Excel::download(new WarehouseItemExport($request->department, $request->branch_id, $request->start_date, $request->end_date), 'invoices.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    private function unprocessedPO($request){
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');
        $po_items = PurchaseOrderDetails::with('item', 'purchaseOrder.purchaseRequest.branch', 'purchaseRequestDetail.recommendedCanvas.vendor')
        ->whereHas('purchaseOrder', function($q1) use($request){
            if($request->department){
                $q1->where('po_Document_warehouse_id', $request->department);
            }
            $q1->where('po_Document_branch_id', $request->branch_id)->whereDoesntHave('delivery');
        })->get();

        if(sizeof($po_items) < 1){
            return response()->json(['error' => 'No data found'], 200);
        }

        $branch = Branchs::find($request->branch_id);
        $warehouse = Warehouses::find($request->department);
        $pdf_data = [
            'items' => $po_items,
            'branch_name' => $branch->name,
            'warehouse_name' => $warehouse->warehouse_description ?? 'ALL Department',
        ];
        $pdf = PDF::loadView('reports.undeliveredPO', ['pdf_data' => $pdf_data]);
        $path = public_path() .'/reports/';
        if(!file_exists($path)){
            mkdir($path);
        }
        $filename = 'undelivered_po'. $branch->id . '.pdf';
        if(file_exists($path . $filename)){
            File::delete($path . $filename);
        }
        $pdf->save($path . $filename);
        return config('app.url') . '/reports/' . $filename;
        // return $pdf->download('undelivered-po'.'.pdf');
    }

    private function unprocessedPR($request){
        $pr_items = PurchaseRequestDetails::with('itemMaster', 'purchaseRequest')->whereDoesntHave('purchaseOrderDetails')
        ->where(function($q1){
            $q1->whereNotNull('pr_Branch_level1_ApprovedBy')->orWhereNotNull('pr_Branch_level2_ApprovedBy');
        })->whereHas('purchaseRequest', function($q1) use($request){
            $q1->where(['branch_Id' => $request->branch_id, 'warehouse_Id' => $request->department]);
        })->get();

        if(sizeof($pr_items) < 1){
            return response()->json(['error' => 'No data found'], 200);
        }

        // ->whereHas('purchaseOrderDetails', function($q1) use($request){
        //     $q1->where('po_Document_branch_id', $request->branch_id)->where('po_Document_warehouse_id', $request->department)->whereDoesntHave('delivery');
        // })->get();
        $branch = Branchs::find($request->branch_id);
        $warehouse = Warehouses::find($request->department);
        $pdf_data = [
            'items' => $pr_items,
            'branch_name' => $branch->name,
            'warehouse_name' => $warehouse->warehouse_description,
        ];
        $pdf = PDF::loadView('reports.unprocessedPR', ['pdf_data' => $pdf_data]);
        $path = public_path() .'/reports/';
        if(!file_exists($path)){
            mkdir($path);
        }
        $filename = 'unprocessed_pr'. $branch->id . $warehouse->id . '.pdf';
        if(file_exists($path . $filename)){
            File::delete($path . $filename);
        }
        $pdf->save($path . $filename);
        return config('app.url') . '/reports/' . $filename;
        // return $pdf->download('undelivered-po'.'.pdf');
    }

    private function printInvoice($request){
        $delivery = Delivery::with(['branch', 'vendor', 'receiver', 'purchaseOrder.purchaseRequest', 'items' => function ($q) {
            $q->with('item', 'unit');
        }])->where('rr_Document_Invoice_No', $request->invoice)->where('rr_Document_Vendor_Id', $request->vendor)->first();

        if(!$delivery) return response()->json(['error' => 'No data found'], 200); 

        $filename = 'invoice-' . $request->invoice . '.pdf';
        $full_path = config('app.url') . '/reports/' . $filename;
        $path = public_path() .'/reports/';

        if(file_exists($path . $filename)){
            return $full_path;
        }

        $qrCode = QrCode::size(200)->generate($full_path);
        $qrData = base64_encode($qrCode);
        $qrSrc = 'data:image/jpeg;base64,' . $qrData;

        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        $pdf_data = [
            'logo' => $imageSrc,
            'qr' => $qrSrc,
            'delivery' => $delivery,
            'transaction_date' => Carbon::parse($delivery->rr_Document_Transaction_Date)->format('Y-m-d'),
            'po_date' => Carbon::parse($delivery['purchaseOrder']['po_Document_transaction_date'])->format('Y-m-d')
        ];

        $pdf = PDF::loadView('pdf_layout.delivery', ['pdf_data' => $pdf_data]);
        
        if(!file_exists($path)){
            mkdir($path);
        }

        $pdf->save($path . $filename);

        return $full_path;
    }
}
