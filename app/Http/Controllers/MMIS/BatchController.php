<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemModel;

class BatchController extends Controller
{
    public function getItemBatchs(){
        $batchs = ItemBatch::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => Request()->item_id])
            ->where('isConsumed', '!=', 1)->get();

        return response()->json(["batchs" => $batchs], 200);
    }

    public function getItemModels(){
        $models = ItemModel::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => Request()->item_id])
            ->where('isConsumed', '!=', 1)->get();

        return response()->json(["models" => $models], 200);
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            if(ItemBatch::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => $request->item_Id, 'batch_Number' => $request->batch_Number])->exists()){
                return response()->json(["error" => 'Batch number is already exist'], 200);
            }
            if(ItemBatch::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => $request->item_Id])->whereDate('item_Expiry_Date', Carbon::parse($request->item_Expiry_Date))->exists()){
                return response()->json(["error" => 'Expiration date is already exist'], 200);
            }
            $batch = ItemBatch::create([
                'branch_id' => Auth::user()->branch_id,
                'warehouse_id' => Auth::user()->warehouse_id,
                'batch_Number' => $request->batch_Number,
                'batch_Transaction_Date' => Carbon::now(),
                'batch_Remarks' => $request->batch_Remarks,
                'item_Id' => $request->item_Id,
                'item_Qty' => $request->item_Qty,
                'item_UnitofMeasurement_Id' => $request->item_UnitofMeasurement_Id,
                'item_Expiry_Date' => $request->item_Expiry_Date?Carbon::parse($request->item_Expiry_Date):NULL,
                'isConsumed' => 0,
            ]);

            if($request->warehouse_item_id){
                $warehouse_item = Warehouseitems::findOrfail($request->warehouse_item_id);

                $warehouse_item->update([
                    'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$request->item_Qty
                ]);

                $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Physical Count%')->where('isActive', 1)->first();
                InventoryTransaction::create([
                    'branch_Id' => Auth::user()->branch_id,
                    'warehouse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'transaction_Item_Id' => $request->item_Id,
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $request->item_UnitofMeasurement_Id,
                    'transaction_Qty' => $request->item_Qty,
                    'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $request->item_Qty,
                    'transaction_Item_ListCost' => $warehouse_item->item_ListCost,
                    'transaction_UserID' =>  Auth::user()->id,
                    'createdBy' =>  Auth::user()->id,
                    'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                ]);
                $sequence->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                ]);
            }
    
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv')->commit();
            return response()->json(["batch" => $batch], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_mmis')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function checkAvailability()
    {
        if(ItemBatch::where(['batch_Number' => Request()->batch, 'item_Id' => Request()->item])->exists()){
            return response()->json(['message' => 'duplicate'], 200);
        }
        return response()->json(['message' => 'available'], 200);
    }

    public function update(Request $request, ItemBatch $batch){

    }

    public function storeModel(Request $request){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            if(ItemModel::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => $request->item_Id, 'model_Number' => $request->batch_Number])->exists()){
                return response()->json(["error" => 'Batch number is already exist'], 200);
            }
            if(ItemModel::where(['warehouse_id' => Auth::user()->warehouse_id, 'item_Id' => $request->item_Id, 
            'model_SerialNumber' => $request->model_SerialNumber, 'model_Number' => $request->batch_Number])->exists()){
                return response()->json(["error" => 'Serial number is already exist'], 200);
            }
            $batch = ItemModel::create([
                'branch_id' => Auth::user()->branch_id,
                'warehouse_id' => Auth::user()->warehouse_id,
                'model_Number' => $request->batch_Number,
                'model_Transaction_Date' => Carbon::now(),
                'model_Remarks' => $request->batch_Remarks,
                'item_Id' => $request->item_Id,
                'item_Qty' => $request->item_Qty,
                'item_UnitofMeasurement_Id' => $request->item_UnitofMeasurement_Id,
                'model_SerialNumber' => $request->model_SerialNumber,
                'isConsumed' => 0,
            ]);

            if($request->warehouse_item_id){
                $warehouse_item = Warehouseitems::findOrfail($request->warehouse_item_id);

                $warehouse_item->update([
                    'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$request->item_Qty
                ]);

                $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Physical Count%')->where('isActive', 1)->first();
                InventoryTransaction::create([
                    'branch_Id' => Auth::user()->branch_id,
                    'warehouse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'transaction_Item_Id' => $request->item_Id,
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $request->item_UnitofMeasurement_Id,
                    'transaction_Qty' => $request->item_Qty,
                    'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $request->item_Qty,
                    'transaction_Item_ListCost' => $warehouse_item->item_ListCost,
                    'transaction_UserID' =>  Auth::user()->id,
                    'createdBy' =>  Auth::user()->id,
                    'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                ]);
                $sequence->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                ]);
            }
    
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["batch" => $batch], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }
}
