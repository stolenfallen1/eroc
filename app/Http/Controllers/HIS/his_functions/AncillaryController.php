<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Models\HIS\medsys\tbInvStockCard;
use Illuminate\Http\Request;

class AncillaryController extends Controller
{
    //
    public function getAncillaryPatients() 
    {
        $data = tbInvStockCard::query();
        $data->where('ItemID', '10315');
        return response()->json($data->get());
    }
}
