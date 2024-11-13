<?php

namespace App\Helpers;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\ItemBatchModelMasterLogs;
use Exception;

class RecomputePrice
{

  protected $model;
  protected $model_item_master;
  public function __construct() {}

  public function compute($warehouse_id = null, $batch_id = null, $item_id = null, $markup_type = null)
  {
    try {
      DB::connection('sqlsrv_mmis')->beginTransaction();
      $item_batch = ItemBatchModelMaster::where('warehouse_id', $warehouse_id)
        ->where('branch_id', auth()->user()->branch_id)
        ->where('item_Id', $item_id)
        ->get();

      $item_master = Warehouseitems::where('warehouse_Id', $warehouse_id)
        ->where('branch_id', auth()->user()->branch_id)
        ->where('item_Id', $item_id)
        ->first();

      $total_item_qty       = 0;
      $total_item_onhand    = 0;
      $total_item_qty_used  = 0;
      $total_price          = 0;
      $markup_in            = 0;
      $markup_out           = 0;
      $mark_up_in_amount    = 0;
      $mark_up_out_amount   = 0;

      foreach ($item_batch as $item) {
        $total_item_qty       += $item->item_Qty;
        $total_item_qty_used  += $item->item_Qty_Used;
        $total_price          += ($item->item_Qty - $item->item_Qty_Used) * $item->price;
      }

      $total_item_onhand = (float) ($total_item_qty - $total_item_qty_used);
      $total_item_amount = (float) $total_price;

      // Avoid division by zero
      $averagecost = $total_item_onhand > 0 ? (float) ($total_item_amount / $total_item_onhand) : 0;

      if ($warehouse_id == '78') {
        $markup_in          = $item_master->item_Markup_In / 100;
        $mark_up_in_amount  = (float) $averagecost * $markup_in;
      } else {
        $markup_out         = $item_master->item_Markup_Out / 100;
        $mark_up_out_amount = (float) $averagecost * $markup_out;
      }

      // Round values to four decimal places
      $averagecost        = round($averagecost, 4);
      $mark_up_in_amount  = round($mark_up_in_amount, 4);
      $mark_up_out_amount = round($mark_up_out_amount, 4);
      $total_item_onhand  = round($total_item_onhand, 4);

      $item_master->item_AverageCost        = $averagecost;
      // $item_master->item_ListCost           = $averagecost + $mark_up_in_amount;
      $item_master->item_OnHand             = $total_item_onhand;
      $item_master->item_Selling_Price_In   = $averagecost + $mark_up_in_amount;
      $item_master->item_Selling_Price_Out  = $averagecost + $mark_up_out_amount;
      $item_master->save();
      DB::connection('sqlsrv_mmis')->commit();
    } catch (Exception $e) {
      DB::connection('sqlsrv_mmis')->rollBack();

      return response()->json($e->getMessage(), 200);
    }
  }
}
