<?php

namespace App\Models\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\MedsysOutpatient;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\MedsysPatientAllergies;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysPatientMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbmaster'; 
    protected $primaryKey = 'HospNum';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['patient_name'];

    public function getPatientNameAttribute()
    {
        return $this->LastName . ', ' .$this->FirstName. ' ' .$this->MiddleName;
    }
    public function opd_registry(){
        return $this->belongsTo(MedsysOutpatient::class,'HospNum', 'HospNum')->whereNull('DcrDate');
    }

    public function hemodialysis_registry(){
        return $this->belongsTo(MedsysHemoPatient::class,'HospNum', 'HospNum');
    }

    public function patient_registry(){
        return $this->belongsTo(MedsysOutpatient::class,'HospNum', 'HospNum')->whereDate('AdmDate', Carbon::now()->format('Y-m-d'))->whereNull('DcrDate');
    }

    public function patientmaster_other_details(){
        return $this->belongsTo(MedsysPatientMasterDetails::class,'HospNum', 'HospNum');
    }
    public function patient_Inpatient(){
        return $this->belongsTo(MedsysInpatient::class,'HospNum', 'HospNum')->whereNull('DcrDate')->select('IdNum','HospNum','AdmDate','DcrDate');
    }

    public function patient_allergies(){
        return $this->belongsTo(MedsysPatientAllergies::class,'HospNum', 'HospNum');
    }

}
