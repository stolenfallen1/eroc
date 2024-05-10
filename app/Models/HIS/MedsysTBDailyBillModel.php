<?php

namespace App\Models\HIS;

use App\Models\HIS\MedsysInpatient;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\FmsExamProcedureItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysTBDailyBillModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_billing';
    protected $table = 'BILLING.dbo.tbBillDailyBill';
    protected $guarded = [];
    public $timestamps = false;
    
    protected $with = ['items','doctor_details','inpatient_datails'];

    public function items(){
        return $this->belongsTo(FmsExamProcedureItems::class,'ItemID', 'map_item_id');
    }

    public function doctor_details(){
        return $this->belongsTo(Doctor::class,'ItemID', 'doctor_code');
    }

    public function inpatient_datails(){
        return $this->belongsTo(MedsysInpatient::class,'IdNum', 'IdNum')->with('patient_details','station_details');
    }
}
