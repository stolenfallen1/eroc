<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierPaymentCode extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscCashierPaymentCode';
    protected $guarded = [];
}
