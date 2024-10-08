<?php

namespace App\Models\HIS;

use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPhysicalExamtionChestLungs extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPhysicalExamtionChestLungs';
    protected $primaryKey = 'case_No';
    protected $guarded = [];
    public function patientMaster() 
    {
        return $this->hasOne(Patient::class, 'patient_Id', 'patient_Id');
    }
    public function patientRegistry()
    {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
}
