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
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\ItemModel;
use Illuminate\Bus\Batch;

class StockRequisitionController extends Controller
{
    public function index(){
        return (new StockRequisitions)->searchable();
    }

    public function show(StockRequisition $stock_requisition){
        return response()->json(['sr' => $stock_requisition->load('requestedBy', 'requesterWarehouse', 'requesterBranch', 'senderWarehouse', 
            'senderBranch', 'transferBy', 'category', 'receivedBy', 'items.item.wareHouseItem', 'items.batches.batch')]);
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
            if($request->sender_branch_id != $authUser->branch_id){
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

    public function releaseStock(Request $request, StockRequisition $stock_requisition){
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            foreach ($request->items as $item) {
                $sr_item = StockRequisitionItem::with('batches')->findOrfail($item['id']);
                foreach ($item['batches'] as $batch) {
                    $sr_item->batches()->create([
                        'batch_id' => $batch['batch']['id'],
                        'qty' => $batch['item_Qty'],
                        'sender_warehouse_id' => $stock_requisition['sender_warehouse_id'],
                        'receiver_warehouse_id' => $stock_requisition['requester_warehouse_id'],
                    ]);
                }
                $sr_item->update([
                    'quantity' => $item['quantity']
                ]);
            }
            $stock_requisition->update([
                'transfer_by_id' => Auth::user()->idnumber
            ]);

            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }

    }

    public function receiveTransfer(Request $request, StockRequisition $stock_requisition)
    {
        // return 'checking';
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $stock_requisition->load('items');
            $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Stock Requisition%')->where('isActive', 1)->first();
            $transaction1 = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Received Requisition%')->where('isActive', 1)->first();
            if(!$transaction || !$transaction1) return response()->json(['error' => 'Transaction code no found'], 200);
            $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            if(!$sequence) return response()->json(['error' => 'Sequence no found'], 200);
            // $delivery = Delivery::with('items.batchs')->where('id', $stock_transfer->delivery_id)->first();
            foreach($request->items as $item) {

                $receiver_warehouse = Warehouseitems::where([
                    'branch_id' => $stock_requisition['requester_branch_id'],
                    'warehouse_Id' => $stock_requisition['requester_warehouse_id'],
                    'item_Id' => $item['item_id'],
                ])->first();

                $sender_warehouse = Warehouseitems::where([
                    'warehouse_Id' => $stock_requisition->sender_warehouse_id,
                    'branch_id' => $stock_requisition->sender_branch_id,
                    'item_Id' => $item['item_id'],
                ])->first();

                $sr_item = StockRequisitionItem::where('id', $item['id'])->first();

                $batch=null;

                foreach ($item['batches'] as $batch) {

                    $batchS = ItemBatchModelMaster::whereDate('item_Expiry_Date', Carbon::parse($batch['batch']['item_Expiry_Date'])->toDateString())
                    ->where(['item_Id' => $batch['batch']['item_Id'], 'warehouse_id' => $batch['batch']['warehouse_id'], 
                    'batch_Number' => $batch['batch']['batch_Number']])->first();

                    $batchS->update([
                        'item_Qty' => $batch->item_Qty - $batch->qty
                    ]);

                    $batchR = ItemBatchModelMaster::whereDate('item_Expiry_Date', Carbon::parse($batch['batch']['item_Expiry_Date'])->toDateString())
                    ->where(['item_Id' => $batch['batch']['item_Id'], 'warehouse_id' => $stock_requisition->requester_warehouse_id, 
                    'branch_id' => $stock_requisition->requester_branch_id, 'batch_Number' => $batch['batch']['batch_Number']])->first();

                    if($batchR){
                        $batchR->update([
                            'item_Qty' => $batch->item_Qty + $batch->qty
                        ]);
                    }else {
                        ItemBatchModelMaster::create([
                            'batch_Number' => $batch['batch']['batch_Number'],
                            'model_Number' => $batch['batch']['batch_Number'],
                            'batch_Transaction_Date' => Carbon::now(),
                            'branch_id' => $stock_requisition->requester_branch_id,
                            'warehouse_id' => $stock_requisition->requester_warehouse_id,
                            'item_Qty' => $batch['qty'],
                            'item_Id' => $batch['batch']['item_Id'],
                            'item_Expiry_Date' => Carbon::parse($batch['batch']['item_Expiry_Date']),
                            'isConsumed' => 0,
                            'price' => $batch['price'],
                            'mark_up' => $batch['mark_up'],
                        ]);
                    }

                    // if($item['item']['ware_house_item']['isLotNo_Required']=="1"){
                    //     $batchS = ItemBatch::whereDate('item_Expiry_Date', Carbon::parse($batch['batch']['item_Expiry_Date'])->toDateString())
                    //     ->where(['item_Id' => $batch['batch']['item_Id'], 'warehouse_id' => $batch['batch']['warehouse_id'], 
                    //     'batch_Number' => $batch['batch']['batch_Number']])->first();

                    //     $batchS->update([
                    //         'item_Qty' => $batch->item_Qty - $batch->qty
                    //     ]);

                    //     $batchR = ItemBatch::whereDate('item_Expiry_Date', Carbon::parse($batch['batch']['item_Expiry_Date'])->toDateString())
                    //     ->where(['item_Id' => $batch['batch']['item_Id'], 'warehouse_id' => $stock_requisition->requester_warehouse_id, 
                    //     'branch_id' => $stock_requisition->requester_branch_id, 'batch_Number' => $batch['batch']['batch_Number']])->first();

                    //     if($batchR){
                    //         $batchR->update([
                    //             'item_Qty' => $batch->item_Qty + $batch->qty
                    //         ]);
                    //     }else {
                    //         ItemBatch::create([
                    //             'batch_Number' => $batch['batch']['batch_Number'],
                    //             'batch_Transaction_Date' => Carbon::now(),
                    //             'branch_id' => $stock_requisition->requester_branch_id,
                    //             'warehouse_id' => $stock_requisition->requester_warehouse_id,
                    //             'item_Qty' => $batch['qty'],
                    //             'item_Id' => $batch['batch']['item_Id'],
                    //             'item_Expiry_Date' => Carbon::parse($batch['batch']['item_Expiry_Date']),
                    //             'isConsumed' => 0,
                    //         ]);
                    //     }
                    // }else{
                    //     $model = ItemModel::where(['item_Id' => $batch['batch']['item_Id'], 'warehouse_id' => $batch['batch']['warehouse_id']])->first();
                    // }
                    
                }

                $receiver_warehouse->update([
                    'item_OnHand' => (float)$receiver_warehouse->item_OnHand + (float)$item['received_qty']
                ]);

                InventoryTransaction::create([
                    'branch_Id' => $stock_requisition['requester_branch_id'],
                    // 'warehouse_Group_Id' => $stock_requisition->rr_Document_Warehouse_Group_Id,
                    'warehouse_Id' => $stock_requisition['requester_warehouse_id'],
                    'transaction_Item_Id' =>  $item['item_id'],
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $item['unit_id'],
                    'transaction_Qty' => $item['received_qty'],
                    'transaction_Item_OnHand' => $receiver_warehouse->item_OnHand + $item['received_qty'],
                    'transaction_Item_ListCost' => $sender_warehouse->item_ListCost,
                    'transaction_UserID' =>  Auth::user()->idnumber,
                    'createdBy' =>  Auth::user()->idnumber,
                    'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                ]);

                $sequence->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ""),
                ]);
                    
                $sender_warehouse->update([
                    'item_OnHand' => (float)$sender_warehouse->item_OnHand - (float)$item['received_qty']
                ]);

                $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
    
                    InventoryTransaction::create([
                        'branch_Id' =>  $stock_requisition['sender_branch_id'],
                        // 'warehouse_Group_Id' => Auth::user()->warehouse->warehouse_Group_Id,
                        'warehouse_Id' => $stock_requisition['sender_warehouse_id'],
                        'transaction_Item_Id' =>  $item['item_id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        'transaction_Item_UnitofMeasurement_Id' => $item['unit_id'],
                        'transaction_Qty' => $item['received_qty'],
                        'transaction_Item_OnHand' => $receiver_warehouse->item_OnHand - $item['received_qty'],
                        'transaction_Item_ListCost' =>  $sender_warehouse->item_ListCost,
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction1->transaction_code ?? '',
                    ]);
    
                    $sequence1->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                    ]);

            }
            
            $stock_requisition->update([
                'received_by' => $authUser->idnumber,
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
