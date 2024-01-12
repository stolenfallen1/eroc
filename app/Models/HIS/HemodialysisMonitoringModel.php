<?php

namespace App\Models\HIS;

use App\Models\HIS\PatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\FmsExamProcedureItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HemodialysisMonitoringModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'HemodialysisMonitoring';
    protected $guarded = [];
    public $timestamps = false;
    protected $with = ['items','patient_details'];

    public function items(){
        return $this->belongsTo(FmsExamProcedureItems::class,'item_id', 'map_item_id');
    }

    public function patient_details(){
        return $this->belongsTo(PatientMaster::class,'pid', 'patient_id');
    }
}
