<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAllergies;

class PatientCauseofAllergy extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientCauseofAllergy';
    protected $guarded = [];
    public function allergies() {
        return $this->belongsTo(PatientAllergies::class, 'id', 'assessID');
    }
}
