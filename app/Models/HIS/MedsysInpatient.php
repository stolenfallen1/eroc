<?php

namespace App\Models\HIS;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysInpatient extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbpatient';
    protected $guarded = [];

    public function patient_details(){
        return $this->belongsTo(MedsysPatientMaster::class,'HospNum', 'HospNum');
    }
}
