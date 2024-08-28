<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientMaster;

class PatientPastBadHabits extends Model
{
    use HasFactory;
    protected $connecttion = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPastBadHabits';
    protected $guarded = [];

    public function patientMaster() {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }

}
