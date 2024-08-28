<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientGynecologicalConditions;
use App\Models\HIS\PatientPregnancyHistory;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\PatientRegistry;

class PatientOBGYNHistory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientOBGYNHistory';
    protected $guarded = [];

    public function patientMaster() {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }

    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
    public function gynecologicalConditions() {
        return $this->hasOne(PatientGynecologicalConditions::class,'OBGYNHistoryID','id');
    }

    public function PatientPregnancyHistory() {
        return $this->hasOne(PatientPregnancyHistory::class,'OBGYNHistoryID','id');
    }
}
