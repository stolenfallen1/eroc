<?php

namespace App\Models\HIS;

use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\FmsExamProcedureItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BillingOutModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'BillingOut';
    protected $guarded = [];
    public $timestamps = false;
    protected $with = ['items','doctor_details','requesting_doctor_details'];


    public function patient_registry(){
        return $this->belongsTo(PatientRegistry::class,'pid', 'patient_id');
    }

    public function items(){
        return $this->belongsTo(FmsExamProcedureItems::class,'item_id', 'map_item_id');
    }

    public function doctor_details(){
        return $this->belongsTo(Doctor::class,'item_id', 'doctor_code');
    }

    public function requesting_doctor_details(){
        return $this->belongsTo(Doctor::class,'request_doctors_id', 'doctor_code');
    }
}
