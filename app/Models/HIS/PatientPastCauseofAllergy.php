<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAllergies;

class PatientPastCauseofAllergy extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPastCauseofAllergy';
    
    protected $guarded = [];

    protected $primaryKey = 'history_Id';

    public function allergy() {
        return $this->belongsTo(PatientAllergies::class,'id', 'history_Id');
    }
}
