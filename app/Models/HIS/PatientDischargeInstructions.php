<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function dischargeDoctosFollowUp() {
        return $this->hasOne(PatientDischargeDoctorsFollowUp::class, 'instruction_Id', 'id');
    }

    public function dischargeFollowUoTreatment() {
        return $this->hasOne(PatientDischargeFollowUpTreatment::class, 'instruction_Id', 'id');
    }

    public function dischargeMedications() {
        return $this->hasOne(PatientDischargeMedications::class, 'instruction_Id', 'id');
    }

    public function dischargeFollowUpLaboratories() {
        return $this->hasOne(PatientDischargeFollowUpLaboratories::class,'instruction_Id', 'id');
    }

}


