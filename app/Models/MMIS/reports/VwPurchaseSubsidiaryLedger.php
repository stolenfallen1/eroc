<?php

namespace App\Models\MMIS\reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VwPurchaseSubsidiaryLedger extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwInventoryReportPurchaseSubsidiaryLedger';
    protected $guarded = [];
}
