<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\CaseIndicators;
use Illuminate\Http\Request;

class CaseIndicatorController extends Controller
{
    //
    public function list()
    {
        $data = CaseIndicators::get();
        return response()->json($data, 200);
    }
}
