<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientOBGYNHistory;

class PatientPregnancyHistory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPregnancyHistory';
    protected $guarded = [];
    
    protected function OBGYNHistory() {
        return $this->hasOne(PatientOBGYNHistory::class, 'id','OBGYNHistoryID');
    }
}
