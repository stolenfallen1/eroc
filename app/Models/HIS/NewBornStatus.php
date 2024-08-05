<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewBornStatus extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'BILLING.dbo.tbBillDailyBill';
    protected $guarded = [];
    public $timestamps = false;
}
