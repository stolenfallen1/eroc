<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAllergies;

class PatientDrugUsedForAllergy extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientDrugUsedForAllergy';
    protected $guarded = [];

    public function allergies() {
        return $this->belongsTo(PatientAllergies::class, 'id', 'assessID');
    }
}