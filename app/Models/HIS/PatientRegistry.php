<?php

namespace App\Models\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientRegistry extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data_v1';
    protected $table = 'CDG_PATIENT_DATAv1.dbo.PatientRegistry';
    protected $guarded = [];

    public function patient_details( ) {
        return $this->belongsTo(PatientMaster::class,'patient_id', 'patient_id');
    }
}
