<?php

namespace App\Models\BuildFile\Hospital;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\FmsTransactionCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalExamItemCategory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscExamItemCategory';
    protected $guarded = [];


    
    public function revenue(){
        return $this->belongsTo(FmsTransactionCode::class, 'fms_transaction_id', 'id');
    }
}
