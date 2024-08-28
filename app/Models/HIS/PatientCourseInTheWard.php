<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientRegistry;

class PatientCourseInTheWard extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientCourseInTheWard';
    protected $guarded = [];
    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
}
