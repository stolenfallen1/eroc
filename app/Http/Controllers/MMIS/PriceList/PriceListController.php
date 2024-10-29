<?php

namespace App\Http\Controllers\MMIS\PriceList;

use PDF;
use Illuminate\Http\Request;
use App\Jobs\GeneratePdfReport;
use App\Jobs\PriceListPDFReport;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Barryvdh\Snappy\Facades\SnappyPdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\PriceList\InventoryPriceListAll;
use App\Models\MMIS\PriceList\InventoryPriceListPerLocation;
use TCPDF;
class PriceListController extends Controller
{
    public function allPriceList(Request $request)
    {
        $location_id = $request->payload['warehouse_id'] ?? '';


        if ($location_id) {
            $data  = InventoryPriceListPerLocation::getReport($location_id);
        } else {
            $data  = InventoryPriceListAll::whereIn('LocationID', Auth()->user()->departments)->get();
        }
        return response()->json($data, 200);
    }


    public function printAllLocation(Request $request)
    {
        $classification_id = Request()->type ?? '';
        $locationid = Request()->location_id;
        set_time_limit(600);
        if ($classification_id == '1' || $classification_id == '') {
            GeneratePDFReport::dispatch($locationid);
        }
        if ($classification_id == '2') {
            PriceListPDFReport::dispatch($locationid);
        }
        $data['locationid'] = Request()->location_id;
        $data['idnumber'] = Request()->location_id;
        $data['type'] = $classification_id;

        return view('reports.price-list.view', $data);
    }

    public function priceList(Request $request)
    {

        $warehouse = $request->warehouse;
        $response = Http::get('http://10.4.15.15:3006/api/price-list/generate-pdf?warehouse='.$warehouse);
        // // To get the response data
        $data = $response->json(); // If the response is JSON
        $branch = Branchs::where('id', 1)->first();
        $pdf_data = [
            'branch' => $branch,
            'warehouse' => 1,
            'dateFrom' => date('m/d/Y'),
            'dateTo' => date('m/d/Y'),
            'Type' => 1,
            'items' => $data,
        ];

        // Create a new TCPDF instance
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Company');
        $pdf->SetTitle('Inventory Price List');
        $pdf->SetSubject('Price List');
        $pdf->SetKeywords('TCPDF, PDF, price list, inventory');

        // Set default header and footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins (left, top, right)
        $pdf->SetMargins(10, 10, 10); // Set left, top, and right margins to 10mm
        $pdf->setCellPadding(1); // Adjust the padding as needed

        // Add a page
        $pdf->AddPage('L'); // Change to 'P' for portrait if needed

        // Convert the data into HTML
        $htmlContent = view('reports.price-list.all-item-by-location', ['pdf_data' => $pdf_data])->render();

        // Write the HTML content to the PDF
        $pdf->writeHTML($htmlContent, true, false, true, false, '');

        // Optionally output the PDF file
        $pdf->Output('price_list.pdf', 'I'); // 'I' for inline, 'D' for download

    }
}
