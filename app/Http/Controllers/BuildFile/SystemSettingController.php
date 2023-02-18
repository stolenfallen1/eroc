<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function getPRSNSequences(){
        $prsn = SystemSequence::where('seq_description', 'like', '%Purchase Requisition Series Number%')->where('isActive', true)->first();
        return response()->json(["settings" => $prsn], 200);
    }
}
