<?php


namespace App\Models\MMIS\reports;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class InventoryReportPurchaseSubsidiaryLedger extends Model
{
    use HasFactory;
    public static function getReport($location_id = null, $supplier_id = null, $purchase_type = null, $dateFrom = null, $dateTo = null)
    {
        return DB::select('EXEC CDG_MMIS.dbo.SP_Inv_ReportPurchaseSubsidiaryLedger_Per_Supplier ?, ?, ?, ?, ?', [
            $location_id,
            $supplier_id,
            $purchase_type,
            $dateFrom,
            $dateTo
        ]);
    }
}
