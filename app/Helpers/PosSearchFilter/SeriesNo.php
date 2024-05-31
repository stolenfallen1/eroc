<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;

class SeriesNo
{

    public function get_sequence($seq_prefix,$terminal_code){
        return SystemSequence::where('code',$seq_prefix)->where('terminal_code',$terminal_code)->where('branch_id',Auth()->user()->branch_id)->first();
    }
    public function generate_series($seq_no,$digit)
    {
        return str_pad($seq_no, $digit, "0", STR_PAD_LEFT);
    }
}
