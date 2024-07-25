<?php

namespace App\Models\HIS\his_functions;

// use App\Models\BuildFile\FmsExamProcedureItems;
// use App\Models\BuildFile\Hospital\Doctor;
use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashORMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'CashORMaster';
    protected $guarded = [];
    public $timestamps = false;

    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'HospNum', 'patient_id');
    }
    // public function items() {
    //     return $this->belongsTo(FmsExamProcedureItems::class, 'itemID', 'map_item_id');
    // }
    // public function doctor_details() {
    //     return $this->belongsTo(Doctor::class, 'itemID', 'doctor_code');
    // }
}
