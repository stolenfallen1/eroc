<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientPastCauseofAllergy;

class PatientPastAllergyHistory extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPastAllergyHistory';
    protected $guarded = [];

    public function pastCauseOfAllergy() {
        return $this->hasMany(PatientPastCauseofAllergy::class,'history_Id','id');
    }

    public function pastSymptomsOfAllergy() {
        return $this->hasMany(PatientPastSymptomsofAllergy::class,'history_Id','id');
    }

}

