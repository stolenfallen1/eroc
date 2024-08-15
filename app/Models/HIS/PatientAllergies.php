<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientSymptomsofAllergy;
use App\Models\HIS\PatientCauseofAllergy;

class PatientAllergies extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAllergies';
    protected $guard = [];

    public function symptoms_allergy() {
        return $this->hasMany(PatientSymptomsofAllergy::class,'history_Id','patient_Id');
    }
    public function cause_of_allergy() {
        return $this->hasMany(PatientCauseofAllergy::class,'history_Id','patient_Id');
    }
}
