<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientDischargeInstructions;

class PatientDischargeFollowUpTreatment extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientDischargeFollowUpTreatment';
    protected $guarded = [];

    public function dischargeInstructions() {
        return $this->belongsTo(PatientDischargeInstructions::class, 'id', 'instruction_Id');
    }

}
