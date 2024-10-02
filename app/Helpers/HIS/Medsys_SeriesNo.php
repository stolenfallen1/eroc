<?php

namespace App\Helpers\HIS;

use App\Models\HIS\MedsysSeriesNo;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;

class Medsys_SeriesNo
{

    public function get_sequence(){
        return MedsysSeriesNo::select('HospNum','IDNum')->where('HemoNum','0')->first();
    }
    public function get_opd_sequence(){
        return MedsysSeriesNo::select('OPDId','IDNum')->where('HemoNum','0')->first();
    }
    
    public function generate_series($seq_no,$digit)
    {
        return str_pad($seq_no, $digit, "0", STR_PAD_LEFT);
    }

    public function get_er_sequence() {
        return MedsysSeriesNo::select('HospNum', 'IDNum', 'ERNum')->where('HemoNum', '0')->first();
    }
}
