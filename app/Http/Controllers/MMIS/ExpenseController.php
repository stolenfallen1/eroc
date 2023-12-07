<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\procurement\ExpenseIssuance;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Helpers\SearchFilter\inventory\ExpenseRequisitions;

class ExpenseController extends Controller
{
    public function index(){
        return (new ExpenseRequisitions)->searchable();
    }

    public function store(Request $request){
        $auth_user = Auth::user();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $expense = ExpenseIssuance::create([
                'warehouse_id' => $auth_user->warehouse_id,
                'branch_id' => $auth_user->branch_id,
                'section_id' => $request->section_id,
                'item_id' => $request->item_id,
                'created_by' => $auth_user->idnumber,
                'reason' => $request->reason,
                'quantity' => $request->quantity,
                'batch_id' => $request->batch_id,
                'item_unit_measurement_id' => $request->item_unit_measurement_id,
            ]);

            $item_batch = ItemBatchModelMaster::where('id', $request->batch_id)->first();
            $item_batch->update([
                'item_Qty' => $item_batch->item_Qty - $request->quantity
            ]);

            $warehouse_item = Warehouseitems::where(['warehouse_Id' => $expense->warehouse_id, 'branch_id' => $expense->branch_id])
            ->where('item_Id', $expense->item_id)->first();
            $warehouse_item->update([
                'item_OnHand' => $warehouse_item->item_OnHand - $expense->quantity
            ]);

            $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Expense Issuance%')->where('isActive', 1)->first();

            InventoryTransaction::create([
                'branch_Id' => $expense->branch_id,
                'warehouse_Id' => $expense->warehouse_id,
                'transaction_Item_Id' =>  $expense->item_id,
                'transaction_Date' => Carbon::now(),
                'trasanction_Reference_Number' => $expense->id,
                'transaction_Item_UnitofMeasurement_Id' => $expense->item_unit_measurement_id,
                'transaction_Qty' => $expense->quantity,
                'transaction_Item_OnHand' => $warehouse_item->item_OnHand - $expense->quantity,
                'transaction_Item_ListCost' => $warehouse_item->item_ListCost,
                'transaction_UserID' =>  $auth_user->idnumber,
                'createdBy' =>  $auth_user->idnumber,
                'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
            ]);

            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv_mmis')->rollBack();
            return response()->json(['error' => $e]);
        }
    }

    public function update(Request $request, $id){
        
    }

    public function destroy($id){

    }
}
