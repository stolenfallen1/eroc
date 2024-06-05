<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\ItemBatchModelMasterLogs;

class BatchTransactionLog{

  protected $model;
  protected $model_item_master;
  public function __construct()
  {
  }
  public function batchTransactionLogs($item_id,$generatesequence){
      $batch = ItemBatchModelMaster::where('warehouse_id',Auth()->user()->warehouse_id)->where('item_Qty','!=',0)->where('branch_id', Auth()->user()->branch_id)->where('item_Id',$item_id)->get();
      foreach($batch as $data){
        ItemBatchModelMasterLogs::updateOrCreate(
          [
            'picklist_number'=>$generatesequence,
            'branch_id'=> $data['branch_id'],
            'item_Id'=> $data['item_Id'] ?? '',            
            'batch_Number'=> $data['batch_Number'] ?? '',
            'warehouse_id'=> $data['warehouse_id'],
          ],
          [      
            'picklist_number'=> $generatesequence ?? '',
            'branch_id'=> $data['branch_id'] ?? '',
            'warehouse_id'=> $data['warehouse_id'] ?? '',
            'batch_Number'=> $data['batch_Number'] ?? '',
            'batch_Transaction_Date'=> $data['batch_Transaction_Date'] ?? '',
            'batch_Remarks'=> $data['batch_Remarks'] ?? '',
            'model_Number'=> $data['model_Number'] ?? '',
            'model_SerialNumber'=> $data['model_SerialNumber'] ?? '',
            'model_Transaction_Date'=> $data['model_Transaction_Date'] ?? '',
            'model_Remarks'=> $data['model_Remarks'] ?? '',
            'item_Id'=> $data['item_Id'] ?? '',
            'item_Qty'=> $data['item_Qty'] ?? '',
            'item_Qty_Used'=> $data['item_Qty_Used'] ?? '',
            'item_UnitofMeasurement_Id'=> $data['item_UnitofMeasurement_Id'] ?? '',
            'item_Expiry_Date'=> $data['item_Expiry_Date'] ?? '',
            'isConsumed'=> ($data['item_Qty'] == $data['item_Qty_Used']) ? 1 : 0,
            'isDeleted'=> $data['isDeleted'] ?? '',
            'delivery_item_id'=> $data['delivery_item_id'] ?? '',
            'price'=> $data['price'] ?? '',
            'mark_up'=> $data['mark_up'] ?? '',
            'isconsignment'=> $data['isconsignment'] ?? '',
            'iscredit'=> $data['iscredit'] ?? '',
            'credit_id'=> $data['credit_id'] ?? '',
            'createdby'=> Auth()->user()->idnumber,
            'created_at'=> Carbon::now(),
            'updated_at'=> Carbon::now(),
            'updatedby'=> Auth()->user()->idnumber,
          ]
        );
    }
  }
}