<?php

namespace App\Http\Controllers\MMIS\Reports;

use PDF;
use Illuminate\Http\Request;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\reports\InventoryReportPurchaseSubsidiaryLedger;
use App\Models\MMIS\reports\InventoryReportPurchaseSubsidiaryLedgerAll;

class PurchaseSubsidiaryReportController extends Controller
{
    public function allsupplier(Request $request)
    {
        $location_id = $request->payload['warehouse_id'] ?? '';
        $supplier_id = $request->payload['vendor_id'] ?? '';
        $purchase_type = $request->payload['purchase_type'] ?? '';
        $dateFrom = $request->payload['dateFrom'];
        $dateTo = $request->payload['dateTo'];
        if($supplier_id){
            $data  = InventoryReportPurchaseSubsidiaryLedger::getReport($location_id, $supplier_id, $purchase_type, $dateFrom, $dateTo);
        }else{
            $data  = InventoryReportPurchaseSubsidiaryLedgerAll::getReport($location_id,$purchase_type, $dateFrom, $dateTo);
        }
        return response()->json($data, 200);
    }

    public function printAllSupplier(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $location_id = $request->input('location_id');
        $supplier_id = $request->input('supplier_id');
        $purchase_type = $request->input('purchase_type');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

        
        // Generate the QR code for the delivery
        $qrCode = QrCode::size(200)->generate(config('app.url') . '/print-delivery/' . $location_id);

        // Load the logo image and encode it in base64
        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        // Encode the QR code in base64
        $qrData = base64_encode($qrCode);
        $qrSrc = 'data:image/jpeg;base64,' . $qrData;

        $branch = Branchs::where('id',1)->first();

        if($supplier_id){
            $data  = InventoryReportPurchaseSubsidiaryLedger::getReport($location_id, $supplier_id, $purchase_type, $dateFrom, $dateTo);
        }else{
            $data  = InventoryReportPurchaseSubsidiaryLedgerAll::getReport($location_id,  $purchase_type, $dateFrom, $dateTo);
        }

        $groupedData = collect($data)->groupBy('SupplierType');

        // Optional: Convert the grouped data into a format that can be easily used in your front end
        $groupedArray = [];
        $warehouse = '';
        $Type = '';

        foreach ($groupedData as $supplierType => $items) {
            $warehouse = $items[0]->Location;
            $Type = $items[0]->SupplierType;
        
            // Group items by SupplierCode while storing SupplierName for display
            $groupedBySupplierCode = [];
        
            foreach ($items as $item) {
                $supplierCode = $item->SupplierCode; // Filter by SupplierCode
                $supplierName = $item->SupplierName;
        
                // Initialize the array for this supplier if it doesn't exist
                if (!isset($groupedBySupplierCode[$supplierCode])) {
                    $groupedBySupplierCode[$supplierCode] = [
                        'supplierName' => $supplierName, // Store SupplierName for display
                        'items' => [],
                    ];
                }
        
                // Add the item to the supplier's 'items' array only if it's valid
                if (!empty($item)) {
                    if($item->qty > 0){
                        $groupedBySupplierCode[$supplierCode]['items'][] = $item;
                    }
                }
            }
        
            // Filter out suppliers with no items
            $filteredSuppliers = array_filter($groupedBySupplierCode, function ($supplier) {
                return !empty($supplier['items']); // Keep suppliers that have items
            });
        
            // Push the grouped data into the final array
            if (!empty($filteredSuppliers)) {
                // Push the filtered grouped data into the final array
                $groupedArray[] = [
                    'supplierType' => $supplierType,
                    'suppliers' => array_values($filteredSuppliers), // Grouped by SupplierCode with items
                ];
            }
        }
        // Prepare the data for the PDF
        $pdf_data = [
            'logo' => $imageSrc,
            'qr' => $qrSrc,
            'branch'=>$branch,
            'warehouse'=> $warehouse,
            'dateFrom'=> date('m/d/Y',strtotime($dateFrom)),
            'dateTo'=> date('m/d/Y',strtotime($dateTo)),
            'Type'=> $Type,
            'groupedSuppliers' => $groupedArray,
        ];
        
        // Generate the PDF using the prepared data
        $pdf = PDF::loadView('reports.subsidiary-ledger.all-supplier', ['pdf_data' => $pdf_data])->setPaper('letter', 'landscape');

        // Render the PDF
        $pdf->render();

        // Add page numbers to the PDF
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");
        $dompdf->get_canvas()->page_text(750, 595, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, [0, 0, 0]);

        // Stream the generated PDF to the browser
        return $pdf->stream('delivery-' . $location_id . '.pdf');
    }
}
