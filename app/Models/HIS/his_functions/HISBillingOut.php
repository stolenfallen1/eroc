<?php

namespace App\Models\HIS\his_functions;

use App\Models\BuildFile\FmsExamProcedureItems;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HISBillingOut extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'BillingOut';
    protected $guarded = [];
    public $timestamps = false;

    public function patientRegistry() {
        return $this->belongsTo(PatientRegistry::class, 'pid', 'patient_id');
    }

    public function items(){
        return $this->belongsTo(FmsExamProcedureItems::class, 'item_id', 'map_item_id');
    }
}
