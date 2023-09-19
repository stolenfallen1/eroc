<?php

namespace App\Helpers\HIS;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;

class SeriesNo
{

    public function get_sequence($code){
        return SystemSequence::where(['code'=>$code,'isActive' => true, 'branch_id' => Auth()->user()->branch_id])->first();
    }
    public function get_opd_sequence($code){
        return SystemSequence::where(['code'=>$code,'isActive' => true, 'branch_id' => Auth()->user()->branch_id])->first();
    }
    
    public function generate_series($seq_no,$digit)
    {
        return str_pad($seq_no, $digit, "0", STR_PAD_LEFT);
    }
}
