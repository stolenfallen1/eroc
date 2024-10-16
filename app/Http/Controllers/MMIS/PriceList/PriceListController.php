<?php

namespace App\Http\Controllers\MMIS\PriceList;

use PDF;
use Illuminate\Http\Request;
use App\Jobs\GeneratePdfReport;
use App\Jobs\PriceListPDFReport;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\MMIS\PriceList\InventoryPriceListAll;
use App\Models\MMIS\PriceList\InventoryPriceListPerLocation;

class PriceListController extends Controller
{
    public function allPriceList(Request $request)
    {
        $location_id = $request->payload['warehouse_id'] ?? '';
        
        
        if($location_id){
            $data  = InventoryPriceListPerLocation::getReport($location_id);
        }else{
            $data  = InventoryPriceListAll::get();
        }
        return response()->json($data, 200);
    }


    public function printAllLocation(Request $request)
    {
        $classification_id = Request()->type ?? '';
        $locationid = Request()->location_id;
        set_time_limit(600);
        if($classification_id == '1' || $classification_id == ''){
            GeneratePDFReport::dispatch($locationid);
        }
        if($classification_id == '2'){
            PriceListPDFReport::dispatch($locationid);
        }
        $data['locationid'] = Request()->location_id;
        $data['idnumber'] = Request()->location_id;
        $data['type'] = $classification_id;
        
        return view('reports.price-list.view',$data);
    }
}
