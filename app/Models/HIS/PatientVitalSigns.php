<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Model;

class PatientVitalSigns extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientVitalSigns';
    protected $guarded = [];
    // protected $with = [''];
    public function patient_registry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
}
