<?php

namespace App\Models\HIS;

use App\Models\HIS\PatientMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Outpatient extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientRegistry';
    protected $guarded = [];

    public function patient_details(){
        return $this->belongsTo(PatientMaster::class,'patient_id', 'id');
    }
}
