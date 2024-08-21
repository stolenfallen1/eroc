<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PrivilegedPointTransfers;
use App\Models\HIS\PrivilegedPointTransactions;
class PatientPrivilegedCard extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPrivilegedCard';
    protected $guarded = [];

    public function privilegedPointTransfers() {
        return $this->hasOne(PrivilegedPointTransfers::class, 'formCard_Id', 'id');
    }

    public function privilegedPointTransactions() {
        return $this->hasOne(PrivilegedPointTransactions::class, 'card_Id', 'id');
    }
}
