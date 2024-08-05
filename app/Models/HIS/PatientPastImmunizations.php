<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPastImmunizations extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPastImmunizations';
    protected $guarded = [];
    // protected $with = [''];
    public function patient_registry() {
        return $this->belongsTo(PatientRegistry::class, 'patient_Id', 'patient_Id');
    }
}
