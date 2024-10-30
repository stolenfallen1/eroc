<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Models\HIS\medsys\tbNurseLogBook;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    //
    public function getPharmacyPatients() 
    {
        $data = tbNurseLogBook::query();
        $data->where('ItemID', '7280');
        $data->where('Dosage', 'PRN');
        return response()->json($data->get());
    }
}
