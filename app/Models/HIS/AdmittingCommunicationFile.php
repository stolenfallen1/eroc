<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;

class AdmittingCommunicationFile extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.AdmittingCommunicationFile';
    protected $guarded = [];

    public $timestamps = false;

    public function patientMaster() {
        return $this->belongsTo(Patient::class, 'patient_Id', 'patient_Id');
    }

    public function patientRegistry()
    {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }
}
