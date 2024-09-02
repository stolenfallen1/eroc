<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientPrivilegedCard;

class PrivilegedPointTransfers extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PrivilegedPointTransfers';
    protected $guarded = [];

    public function privilegedCard() {
        return $this->belongsTo(PatientPrivilegedCard::class,'id','fromCard_Id');
    }
}
