<?php

namespace App\Models\MMIS\Reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class InventoryReportPurchaseSubsidiaryLedgerAll extends Model
{
    use HasFactory;
    public static function getReport($location_id = null,  $purchase_type = null, $dateFrom = null, $dateTo = null)
    {
        // return DB::select('EXEC CDG_MMIS.dbo.SP_Inv_ReportPurchaseSubsidiaryLedger_All ?, ?, ?, ?', [
        //     $location_id,
        //     $purchase_type,
        //     $dateFrom,
        //     $dateTo
        // ]);
    }
}
