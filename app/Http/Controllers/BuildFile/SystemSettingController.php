<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;

class SystemSettingController extends Controller
{
    public function getPRSNSequences(){
        $prsn = SystemSequence::where('seq_description', 'like', '%Purchase Requisition Series Number%')
        ->where(['isActive' => true, 'branch_id' => Auth::user()->branch_id])->first();
        return response()->json(["settings" => $prsn], 200);
    }
}
