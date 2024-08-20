<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientGynecologicalConditions;
use App\Models\HIS\PatientPregnancyHistory;

class PatientOBGYNHistory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientOBGYNHistory';
    protected $guarded = [];

    public function gynecologicalConditions() {
        return $this->belongsTo(PatientGynecologicalConditions::class,'OBGYNHistoryID','id');
    }

    public function PatientPregnancyHistory() {
        return $this->hasMany(PatientPregnancyHistory::class,'OBGYNHistoryID','id');
    }
}
