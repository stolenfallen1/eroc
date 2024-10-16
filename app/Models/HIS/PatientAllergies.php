<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientSymptomsofAllergy;
use App\Models\HIS\PatientCauseofAllergy;
use App\Models\HIS\PatientDrugUsedForAllergy;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\services\PatientRegistry;

class PatientAllergies extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAllergies';
    protected $guarded = [];

    public function patientMaster() {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }
    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
    public function cause_of_allergy() {
        return $this->hasMany(PatientCauseofAllergy::class, 'assessID','id');
    }
    public function drug_used_for_allergy() {
        return $this->hasMany(PatientDrugUsedForAllergy::class, 'assessID','id');
    }
    public function symptoms_allergy() {
        return $this->hasMany(PatientSymptomsofAllergy::class, 'assessID','id');
    }
}
