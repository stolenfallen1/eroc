<?php

namespace App\Models\MMIS\procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwPrTransactionLog extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.VWPRTransactionLogs';

    protected $guarded = [];
}