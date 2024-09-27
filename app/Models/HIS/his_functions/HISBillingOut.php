<?php

namespace App\Models\HIS\his_functions;

use App\Models\BuildFile\FmsExamProcedureItems;
use App\Models\HIS\services\PatientRegistry;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\services\Patient;

class HISBillingOut extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'CDG_BILLING.dbo.BillingOut';
    protected $guarded = [];
    public $timestamps = false;

    public function patient() {
        return $this->belongsTo(Patient::class, 'patient_Id', 'patient_Id');
    }
    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'patient_Id', 'patient_Id');
    }
    public function items(){
        return $this->belongsTo(FmsExamProcedureItems::class, 'itemID', 'map_item_id');
    }
    public function doctor_details(){
        return $this->belongsTo(Doctor::class,'itemID', 'doctor_code');
    }
    public function lab_services() {
        return $this->hasMany(LaboratoryMaster::class, 'profileId', 'itemID');
    }
}
