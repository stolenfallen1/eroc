<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedSysDailyOut extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_billing';
    protected $table = 'BILLING.dbo.tbBillOPDailyOut';
    protected $guarded = []; 
    protected $primaryKey = 'HospNum';
    public $timestamps = false;
}
