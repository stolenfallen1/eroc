<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedSysDailyBill extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_billing';
    protected $table = 'BILLING.dbo.tbBillDailyBill';
    protected $guarded = []; 
    public $timestamps = false;
}
