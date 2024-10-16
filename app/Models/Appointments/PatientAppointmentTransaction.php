<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\BuildFile\FmsProcedures;
class PatientAppointmentTransaction extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientAppointmentTransactions';
    protected $guarded = [];
    
    public function item(){
        return $this->belongsTo(FmsProcedures::class,'item_Id','item_id')->select('pid','item_id','revenueid','revenuecode','description','price','exam_description','id','transaction_code');
    }
}
