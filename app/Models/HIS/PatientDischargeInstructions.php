<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\PatientRegistry;
use App\Models\HIS\PatientDischargeDoctorsFollowUp;
use App\Models\HIS\PatientDischargeFollowUpTreatment;
use App\Models\HIS\PatientDischargeMedications;
use App\Models\HIS\PatientDischargeFollowUpLaboratories;

class PatientDischargeInstructions extends Model
{
    use HasFactory;
    protected $connection  = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientDischargeInstructions';
    protected $guarded = [];

    public function patientMaster() {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }

    public function patientRegister() {
        return $this->belongsTo(PatientRegistry::class,'case_No', 'case_No');
    }

    public function dischargeDoctorsFollowUp() {
        return $this->hasOne(PatientDischargeDoctorsFollowUp::class, 'instruction_Id', 'id');
    }

    public function dischargeFollowUpTreatment() {
        return $this->hasOne(PatientDischargeFollowUpTreatment::class, 'instruction_Id', 'id');
    }

    public function dischargeMedications() {
        return $this->hasOne(PatientDischargeMedications::class, 'instruction_Id', 'id');
    }

    public function dischargeFollowUpLaboratories() {
        return $this->hasOne(PatientDischargeFollowUpLaboratories::class,'instruction_Id', 'id');
    }

}


