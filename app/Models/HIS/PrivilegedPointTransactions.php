<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientPrivilegedCard;

class PrivilegedPointTransactions extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PrivilegedPointTransactions';
    protected $guarded = [];

    // public function privilegedCard() {
    //     return $this->belongsTo(PatientPrivilegedCard::class,'id','card_Id');
    // }
}
