<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\services\Patient;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\PatientRegistry;

class PatientAdministeredMedicines extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAdministeredMedicines';
    protected $guarded = [];
    // protected $with = [''];

    public function patientMaster() {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }

    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'case_No', 'case_No');
    }

}
