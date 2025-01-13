<?php

namespace App\Models\Biometric;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biometrics extends Model
{
    use HasFactory;
    
    protected $connection = "sqlsrv_cdh_payroll";
    protected $table = 'CDH_PAYROLL.dbo.tbtHandPunch_Online';
    protected $guarded = [];

}
