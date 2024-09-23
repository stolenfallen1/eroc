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

    public function pointTransfers() {
        return $this->hasMany(PrivilegedPointTransfers::class, 'fromCard_Id', 'id');
    }

    public function pointTransactions() {
        return $this->hasMany(PrivilegedPointTransactions::class, 'card_Id', 'id');
    }
}
