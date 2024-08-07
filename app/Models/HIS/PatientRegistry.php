<?php

namespace App\Models\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientRegistry extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientRegistry';
    protected $guarded = [];

    public function patient_details( ) {
        return $this->belongsTo(PatientMaster::class,'patient_id', 'patient_id');
    }
    public function vitals() {
        return $this->hasMany(PatientVitalSigns::class, 'case_No', 'case_No');
    }
    public function medical_procedures() {
        return $this->hasMany(PatientMedicalProcedures::class, 'case_No', 'case_No');
    }
    public function immunizations() {
        return $this->hasMany(PatientImmunizations::class, 'case_No', 'case_No');
    }
    public function history() {
        return $this->hasMany(PatientHistory::class, 'case_No', 'case_No');
    }
    public function administered_medicines() {
        return $this->hasMany(PatientAdministeredMedicines::class, 'case_No', 'case_No');
    }
    public function past_immunization() {
        return $this->hasMany(PatientPastImmunizations::class, 'patient_Id', 'patient_Id');
    }
    public function past_medical_history() {
        return $this->hasMany(PatientPastMedicalHistory::class, 'patient_Id', 'patient_Id');
    }
    public function past_medical_procedures() {
        return $this->hasMany(PatientPastMedicalProcedures::class, 'patient_Id', 'patient_Id');
    }
}
