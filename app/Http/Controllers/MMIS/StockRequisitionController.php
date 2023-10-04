<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\StockRequisition;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\StockRequisitionItem;
use App\Helpers\SearchFilter\inventory\StockRequisitions;
use App\Models\MMIS\inventory\ItemBatch;

class StockRequisitionController extends Controller
{
    public function index(){
        return (new StockRequisitions)->searchable();
    }

    public function show(StockRequisition $stock_requisition){
        return response()->json(['sr' => $stock_requisition->load('requestedBy', 'requesterWarehouse', 'requesterBranch', 'senderWarehouse', 'senderBranch', 'transferBy', 'category', 'receivedBy', 'items.item.wareHouseItem')]);
    }

    public function store(Request $request){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $sequence = SystemSequence::where(['isActive' => true, 'branch_id' => $authUser->branch_id])->where('seq_description', 'like', '%Stock requisitions%')->first();
            if(!$sequence) return response()->json(['error' => 'No system sequence set.. Please contact I.T department']);
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            $is_inter_branch = 0;
            if($request->sender_branch_id != $authUser->warehouse_id){
                $is_inter_branch = 1;
            }

            $stock_requisitions = StockRequisition::create([
                'document_number' => $number,
                'document_prefix' => $prefix,
                'document_suffix' => $suffix,
                'request_by_id' => $authUser->idnumber,
                'requester_warehouse_id' => $authUser->warehouse_id,
                'requester_branch_id' => $authUser->branch_id,
                'sender_warehouse_id' => $request->sender_warehouse_id,
                'sender_branch_id' => $request->sender_branch_id,
                'item_group_id' => $request->item_group_id,
                'category_id' => $request->category_id,
                'remarks' => $request->remarks,
                'is_inter_branch' => $is_inter_branch,
            ]);
            
            foreach ($request->items as $key => $item) {
                $stock_requisitions->items()->create([
                    'warehouse_item_id' => $item['ware_house_item']['id']                    ,
                    'item_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                ]);
            }

            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return $stock_requisitions;
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function update(Request $request, StockRequisition $stock_requisition){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $is_inter_branch = 0;
            if($request->sender_branch_id != $authUser->warehouse_id){
                $is_inter_branch = 1;
            }

            $stock_requisition->update([
                'sender_warehouse_id' => $request->sender_warehouse_id,
                'sender_branch_id' => $request->sender_branch_id,
                'item_group_id' => $request->item_group_id,
                'category_id' => $request->category_id,
                'remarks' => $request->remarks,
                'is_inter_branch' => $is_inter_branch,
            ]);
            $stock_requisition->items()->delete();
            foreach ($request->items as $key => $item) {
                $stock_requisition->items()->create([
                    'warehouse_item_id' => isset($item['ware_house_item']) ? $item['ware_house_item']['id'] : $item['item']['ware_house_item']['id'],
                    'item_id' => isset($item['item']) ? $item['item']['id'] : $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                ]);
            }
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return $stock_requisition->refresh();
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function destroy($id){
        $stock_requisition = StockRequisition::whereDoesntHave('items', function($q1){
            $q1->whereNotNull('department_head_approved_by');
        })->where('id', $id)->first();
        if($stock_requisition){
            $stock_requisition->delete();
        }
    }

    public function approve(Request $request, StockRequisition $stock_requisition){
        if(Auth::user()->role->name == 'department head'){
            $this->approvedByDepartmentHead($request, $stock_requisition);
        }elseif (Auth::user()->role->name == 'administrator') {
            $this->approvedByAdministrator($request, $stock_requisition);
        }elseif (Auth::user()->role->name == 'corporate admin') {
            $this->approvedCorporateAdmin($request, $stock_requisition);
        }
    }

    public function releaseStock(StockRequisition $stock_requisition){
        $stock_requisition->update([
            'transfer_by_id' => Auth::user()->idnumber
        ]);
    }

    public function receiveTransfer(StockRequisition $stock_requisition)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $stock_requisition->load();
            $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Stock Requisition%')->where('isActive', 1)->first();
            $transaction1 = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Received Requisition%')->where('isActive', 1)->first();
            $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            // $delivery = Delivery::with('items.batchs')->where('id', $stock_transfer->delivery_id)->first();
            foreach ($stock_requisition->items as $item) {
                if($stock_requisition->item_group_id == 2)
                $batchs = ItemBatch::whereIn('id', $item->batch_ids)->gets();
                foreach ($batchs as $key => $batch) {
                    $is_complete = false;
                    if($item->quantity <= $batch['item_Qty']){
                        $batch->item_Qty = $batch['item_Qty'] - $batch->item_Qty;
                        $is_complete = true;
                    }
                    if(!$is_complete) continue;
                    $sender_warehouse = Warehouseitems::where([
                        'warehouse_Id' => $stock_requisition->sender_warehouse_id,
                        'branch_id' => $stock_requisition->sender_branch_id,
                        'item_Id' => $batch['item_Id'],
                    ])->first();
                        
                    $sender_warehouse->update([
                        'item_OnHand' => (float)$sender_warehouse->item_OnHand - (float)$batch['item_Qty']
                    ]);
    
                    $receiver_warehouse = Warehouseitems::where([
                        'warehouse_Id' => $stock_requisition->receiver_warehouse,
                        'item_Id' => $batch['item_Id'],
                    ])->first();
    
                    $receiver_warehouse->update([
                        'item_OnHand' => (float)$receiver_warehouse->item_OnHand + (float)$batch['item_Qty']
                    ]);
    
                    InventoryTransaction::create([
                        'branch_Id' => $stock_requisition->sender_branch_id,
                        'warehouse_Group_Id' => $stock_requisition->rr_Document_Warehouse_Group_Id,
                        'warehouse_Id' => $stock_requisition->rr_Document_Warehouse_Id,
                        'transaction_Item_Id' =>  $batch['item_Id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                        'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                        'transaction_Qty' => $batch['item_Qty'],
                        'transaction_Item_OnHand' => $receiver_warehouse->item_OnHand - $batch['item_Qty'],
                        'transaction_Item_ListCost' => $item->rr_Detail_Item_ListCost,
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                    ]);
                    // return "test";
                    $sequence->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ""),
                    ]);
    
                    $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
    
                    InventoryTransaction::create([
                        'branch_Id' =>  Auth::user()->branch_id,
                        'warehouse_Group_Id' => Auth::user()->warehouse->warehouse_Group_Id,
                        'warehouse_Id' => Auth::user()->warehouse_id,
                        'transaction_Item_Id' =>  $batch['item_Id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                        'transaction_Qty' => $batch['item_Qty'],
                        'transaction_Item_OnHand' => $receiver_warehouse->item_OnHand + $batch['item_Qty'],
                        'transaction_Item_ListCost' => $item->rr_Detail_Item_ListCost,
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction1->transaction_code ?? '',
                    ]);
    
                    $sequence1->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                    ]);
                }

            }

            $stock_requisition->update([
                'receiver_id' => Auth::user()->idnumber
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    private function approvedByDepartmentHead($request, $stock_requisition){
        $is_declined = true;
        foreach ($request->items as $item) {
            if($item['isapproved']){
                StockRequisitionItem::where('id', $item['id'])->update([
                    'department_head_approved_by' => Auth::user()->idnumber,
                    'department_head_approved_date' => Carbon::now(),
                ]);
                $is_declined = false;
            }else{
                StockRequisitionItem::where('id', $item['id'])->update([
                    'department_head_declined_by' => Auth::user()->idnumber,
                    'department_head_declined_date' => Carbon::now(),
                    'department_head_declined_remarks' => $request['remarks'],
                ]);
            }
        }
        if($is_declined){
            $stock_requisition->update([
                'remarks' => $request->remarks,
            ]);
        }
    }

    private function approvedByAdministrator($request, $stock_requisition){
        $is_declined = true;
        foreach ($request->items as $item) {
            if($item['isapproved']){
                StockRequisitionItem::where('id', $item['id'])->update([
                    'administrator_approved_by' => Auth::user()->idnumber,
                    'administrator_approved_date' => Carbon::now(),
                ]);
                $is_declined = false;
            }else{
                StockRequisitionItem::where('id', $item['id'])->update([
                    'administrator_declined_by' => Auth::user()->idnumber,
                    'administrator_declined_date' => Carbon::now(),
                    'administrator_declined_remarks' => $item['remarks'],
                ]);
            }
        }
        if($is_declined){
            $stock_requisition->update([
                'remarks' => $request->remarks,
            ]);
        }
    }

    private function approvedCorporateAdmin($request, $stock_requisition){
        $is_declined = true;
        foreach ($request->items as $item) {
            if($item['isapproved']){
                StockRequisitionItem::where('id', $item['id'])->update([
                    'corporate_admin_approved_by' => Auth::user()->idnumber,
                    'corporate_admin_approved_date' => Carbon::now(),
                ]);
                $is_declined = false;
            }else{
                StockRequisitionItem::where('id', $item['id'])->update([
                    'corporate_admin_declined_by' => Auth::user()->idnumber,
                    'corporate_admin_declined_date' => Carbon::now(),
                    'corporate_admin_declined_remarks' => $item['remarks'],
                ]);
            }
        }
        if($is_declined){
            $stock_requisition->update([
                'remarks' => $request->remarks,
            ]);
        }
    }
}
