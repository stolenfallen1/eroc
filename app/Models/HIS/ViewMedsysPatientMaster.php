<?php

namespace App\Models\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\MedsysOutpatient;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\MedsysPatientAllergies;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ViewMedsysPatientMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_medsys_core_db';
    protected $table = 'vwMedsysPatientMaster'; 
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['patient_name'];

    public function patientInfo() {
        return $this->belongsTo(Patient::class, 'patient_Id', 'HospNum');
    }

    public function erPatientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'IDnum');
    }

    public function getPatientNameAttribute()
    {
        return $this->lastname . ', ' .$this->firstname. ' ' .$this->middlename;
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
        return $this->belongsTo(MedsysInpatient::class,'HospNum', 'HospNum')->whereNull('DcrDate')->select('IdNum','HospNum','AdmDate','DcrDate','RoomID');
    }

    public function patient_allergies(){
        return $this->belongsTo(MedsysPatientAllergies::class,'HospNum', 'HospNum');
    }


    public function civilstatus(){
        return $this->belongsTo(MedsysOutpatient::class,'HospNum', 'HospNum')->whereNull('DcrDate');
    }

}
