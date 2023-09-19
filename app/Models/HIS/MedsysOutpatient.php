<?php

namespace App\Models\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;

use App\Models\HIS\MedsysGuarantor;
use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\MedsysPatientInformant;
use App\Models\HIS\MedsysPatientOPDHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysOutpatient extends Model
{
    use HasFactory;  
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tboutpatient';
    protected $primaryKey = 'HospNum';
    protected $guarded = [];
    public $timestamps = false;


    public function patient_details(){
        return $this->belongsTo(MedsysPatientMaster::class,'HospNum', 'HospNum');
    }

    public function new_patient_details(){
        return $this->hasOne(PatientMaster::class,'previous_patient_id', 'HospNum');
    }

    public function patient_guarantor(){
        return $this->belongsTo(MedsysGuarantor::class,'IDNum', 'IDNum');
    }
    
    public function patient_informant(){
        return $this->belongsTo(MedsysPatientInformant::class,'IDNum', 'IDNum');
    }

    public function patient_opdHistory(){
        return $this->belongsTo(MedsysPatientOPDHistory::class,'IdNum', 'IDNum');
    }
    
}
