<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientPastCauseofAllergy;

class MedsysOPDMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbOutPatient';
    protected $guarded = [];
    // protected $primaryKey = 'id';
    
    public function pastCauseOfAllergy() {
        return $this->hasMany(PatientPastCauseofAllergy::class,'history_Id', 'id');
    }

    public function pastSymptomsOfAllergy() {
        return $this->hasMany(PatientPastSymptomsofAllergy::class,'history_Id', 'id');
    }

    public $timestamps = false;
}

