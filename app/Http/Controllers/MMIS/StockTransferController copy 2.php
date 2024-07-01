<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\RecomputePrice;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\StockTransferMaster;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Helpers\SearchFilter\inventory\StockTransfer;

class StockTransferController extends Controller
{
    public function index()
    {
        return (new StockTransfer)->searchable();
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $sequence = SystemSequence::where(['isActive' => true, 'branch_id' => $authUser->branch_id])->where('seq_description', 'like', '%Stock transfer%')->first();
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            
            $stock_transfer = StockTransferMaster::create(
                [
                    'document_number' => $number, 
                    'document_prefix' => $prefix,
                    'document_suffix' => $suffix,
                    'sender_warehouse_id' => $request->warehouseid, 
                    'receiver_warehouse_id' => $request->towarehouse_id,
                    'transfer_by' => $authUser->idnumber, 
                    'transfer_date' => Carbon::now(), 
                    'status' => 12,
                    'branch_id' => Auth()->user()->branch_id,
                    'created_at'=> Carbon::now()
                ]
            );
            foreach($request->selecteditems as $item){
                $stock_transfer->stockTransferDetails()->create(
                    [
                        'transfer_item_id'=> $item['id'],
                        'transfer_item_qty'=> (float)$item['qty'],
                        'transfer_item_batch_id'=> $item['batch'],
                        'transfer_item_unit_cost'=> (float)$item['ware_house_item']['item_ListCost'],
                        'transfer_item_total_cost'=> (float)($item['ware_house_item']['item_ListCost'] * $item['qty']),
                    ]
                );
            }


            
            // $delivery = Delivery::with('purchaseOrder')->where('id', $request->delivery_id)->first();
            // $stock_transfer = StockTransfer::create([
            //     'sender_warehouse' => $request->warehouse_id, 
            //     'receiver_warehouse' => $request->towarehouse_id,
            //     'transfer_by' => $authUser->idnumber, 
            //     'delivery_id' => $request->delivery_id, 
            //     'pr_id' => $delivery->purchaseOrder->pr_Request_id,
            //     'po_id' => $delivery->purchaseOrder->id, 
            //     'status' => 12,
            //     'document_number' => $number, 
            //     'document_prefix' => $prefix,
            //     'document_suffix' => $suffix
            // ]);
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return $stock_transfer;
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function receiveTransfer(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {


            $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Stock Transfer%')->where('isActive', 1)->first();
            $transaction1 = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Received Stocks%')->where('isActive', 1)->first();
            $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            $receivestocks = StockTransferMaster::where('id', $id)->first();
          
            $requestpayload = $request->payload;
            $total_transfer_qty = 0;
            $total_received_qty = 0;
            foreach($requestpayload['selecteditems'] as $item){
                $total_transfer_qty += (float)$item['transfer_qty'];
                $total_received_qty += (float)$item['received_item_qty'];
                $selectItem =  $receivestocks->stockTransferDetails()->where('stock_transfer_id',$id)->where('transfer_item_id',$item['transfer_item_id'])->first();
                $receivestocks->stockTransferDetails()->where('stock_transfer_id',$id)->where('transfer_item_id',$item['transfer_item_id'])->update([
                    'received_item_qty'=> (float) $selectItem->received_item_qty + (float)$item['received_item_qty'],
                    'received_item_batch_id'=> $item['transfer_item_batch_id'],
                    'received_item_unit_cost'=> (float)$item['transfer_item_unit_cost'],
                    'received_item_total_cost'=> (float) $selectItem->received_item_total_cost + ((float)($item['transfer_item_unit_cost'] * $item['received_item_qty'])),
                ]);

           
                $sender_warehouse = Warehouseitems::where([
                    'warehouse_Id' => $requestpayload['sender_warehouse_id'],
                    'item_Id' => $item['transfer_item_id'],
                ])->first();
                    
                $sender_warehouse->update([
                    'item_OnHand' => (float)$sender_warehouse->item_OnHand - (float)$item['transfer_item_qty']
                ]);

                $receivedqty = 0;
                $receiver_warehouse = Warehouseitems::where([
                    'warehouse_Id' => $requestpayload['receiver_warehouse_id'],
                    'item_Id' => $item['transfer_item_id'],
                ])->first();
                if($receiver_warehouse){
                    $receivedqty = $receiver_warehouse->item_OnHand;
                }
                $receiver = Warehouseitems::updateOrCreate(
                    [
                        'warehouse_Id' => $requestpayload['receiver_warehouse_id'],
                        'item_Id' => $item['transfer_item_id'],
                    ],
                    [
                        'branch_id' => $sender_warehouse->branch_id,
                        'item_UnitofMeasurement_Id' => $sender_warehouse->item_UnitofMeasurement_Id,
                        'item_ListCost' => $sender_warehouse->item_ListCost,
                        'item_AverageCost' => $sender_warehouse->item_AverageCost,
                        'item_Markup_Out' => $sender_warehouse->item_Markup_Out,
                        'item_Markup_In' => $sender_warehouse->item_Markup_In,
                        'item_Selling_Price_Out' => $sender_warehouse->item_Selling_Price_Out,
                        'item_Selling_Price_In' => $sender_warehouse->item_Selling_Price_In,
                        'item_Minimum_StockLevel' => $sender_warehouse->item_Minimum_StockLevel,
                        'item_OnOrder' => $sender_warehouse->item_OnOrder,
                        'item_OnOrder_UnitofMeasurement_Id' => $sender_warehouse->item_OnOrder_UnitofMeasurement_Id,
                        'item_ReOrder_Buffer_Days' => $sender_warehouse->item_ReOrder_Buffer_Days,
                        'item_ReOrder_Date' => $sender_warehouse->item_ReOrder_Date,
                        'item_Last_Inventory_Count' => $sender_warehouse->item_Last_Inventory_Count,
                        'item_Manual_Count' => $sender_warehouse->item_Manual_Count,
                        'item_Last_RR_Date' => $sender_warehouse->item_Last_RR_Date,
                        'item_Last_RR_Qty' => $sender_warehouse->item_Last_RR_Qty,
                        'item_Last_PR_Date' => $sender_warehouse->item_Last_PR_Date,
                        'item_Last_PR_Qty' => $sender_warehouse->item_Last_PR_Qty,
                        'item_LotNo_Id' => $sender_warehouse->item_LotNo_Id,
                        'item_BatchNo_Id' => $sender_warehouse->item_BatchNo_Id,
                        'item_SerialNo_Id' => $sender_warehouse->item_SerialNo_Id,
                        'item_ModelNo' => $sender_warehouse->item_ModelNo,
                        'isReOrder' => $sender_warehouse->isReOrder,
                        'isLotNo_Required' => $sender_warehouse->isLotNo_Required,
                        'isExpiryDate_Required' => $sender_warehouse->isExpiryDate_Required,
                        'isModelNo_Required' => $sender_warehouse->isModelNo_Required,
                        'isConsignment' => $sender_warehouse->isConsignment,
                        'isPerishable' => $sender_warehouse->isPerishable,
                        'warehouse_Id' => $requestpayload['receiver_warehouse_id'],
                        'item_Id' => $item['transfer_item_id'],
                        'item_OnHand' => (float)$receivedqty + (float)$item['received_item_qty']
                    ]
                );

                $senderBatch = ItemBatchModelMaster::where('id',$item['transfer_item_batch_id'])->where('item_Id',$item['transfer_item_id'])->where('warehouse_id',$requestpayload['sender_warehouse_id'])->first();
                ItemBatchModelMaster::updateOrCreate(
                    [
                        'item_Id' => $item['transfer_item_id'],  
                        'batch_Number' => $senderBatch->batch_Number,
                        'warehouse_id' => $requestpayload['sender_warehouse_id'],
                    ],
                    [
                        'item_Qty_Used' => $senderBatch->item_Qty_Used + $item['transfer_item_qty'],
                        'isConsumed' => (($senderBatch->item_Qty_Used + $item['transfer_item_qty']) == $senderBatch->item_Qty) ? 1 : 0,
                    ]
                );

                $receiverBatch = ItemBatchModelMaster::where('id',$item['transfer_item_batch_id'])->where('item_Id',$item['transfer_item_id'])->where('warehouse_id',$requestpayload['receiver_warehouse_id'])->first();
                $qty = $receiverBatch ? $receiverBatch->item_Qty : 0;
                $qty_used = $receiverBatch ? $receiverBatch->item_Qty_Used : 0;
                $unit = $receiverBatch ? $receiverBatch->item_UnitofMeasurement_Id : $senderBatch->item_UnitofMeasurement_Id;
                ItemBatchModelMaster::updateOrCreate(
                    [
                        'item_Id' => $item['transfer_item_id'],                        
                        'batch_Number' => $receiverBatch ? $receiverBatch->batch_Number : $senderBatch->batch_Number,
                        'warehouse_id' => $requestpayload['receiver_warehouse_id']
                    ],
                    [
                        'warehouse_id' => $requestpayload['receiver_warehouse_id'],
                        'item_Id' => $item['transfer_item_id'],
                        'batch_Number' => $receiverBatch ? $receiverBatch->batch_Number : $senderBatch->batch_Number,
                        'branch_id' => Auth()->user()->branch_id,
                        'batch_Transaction_Date' => $receiverBatch ? $receiverBatch->batch_Transaction_Date : $senderBatch->batch_Transaction_Date,
                        'batch_Remarks' => $receiverBatch ? $receiverBatch->batch_Remarks : $senderBatch->batch_Remarks,
                        'item_UnitofMeasurement_Id' => $receiverBatch ? $receiverBatch->item_UnitofMeasurement_Id : $senderBatch->item_UnitofMeasurement_Id,
                        'delivery_item_id' => $receiverBatch ? $receiverBatch->delivery_item_id : $senderBatch->delivery_item_id,
                        'item_Expiry_Date' => $receiverBatch ? $receiverBatch->item_Expiry_Date : $senderBatch->item_Expiry_Date,
                        'price' => $receiverBatch ? $receiverBatch->price : $senderBatch->price,
                        'mark_up' => $receiverBatch ? $receiverBatch->mark_up : $senderBatch->mark_up,
                        'model_Number' => $receiverBatch ? $receiverBatch->model_Number : $senderBatch->model_Number,
                        'model_SerialNumber' => $receiverBatch ? $receiverBatch->model_SerialNumber : $senderBatch->model_SerialNumber,
                        'model_Transaction_Date' => $receiverBatch ? $receiverBatch->model_Transaction_Date : $senderBatch->model_Transaction_Date,
                        'model_Remarks' => $receiverBatch ? $receiverBatch->model_Remarks : $senderBatch->model_Remarks,
                        'item_Qty' => $qty + $item['received_item_qty'],
                        'isConsumed' => (($qty_used + $item['received_item_qty']) == $qty) ? 1 : 0,
                    ]
                );
                $itemmaster = Itemmasters::find($item['transfer_item_id']);
                InventoryTransaction::create([
                    'branch_Id' => Auth()->user()->branch_id,
                    'warehouse_Group_Id' => $itemmaster->item_InventoryGroup_Id,
                    'warehouse_Id' => $requestpayload['receiver_warehouse_id'],
                    'transaction_Item_Id' => $item['transfer_item_id'],
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' =>$unit,
                    'transaction_Qty' => (float)$item['received_item_qty'],
                    'transaction_Item_OnHand' => (float)$receivedqty + $item['received_item_qty'],
                    'transaction_Item_ListCost' => (float)$item['transfer_item_unit_cost'],
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
                    'branch_Id' => Auth()->user()->branch_id,
                    'warehouse_Group_Id' => $itemmaster->item_InventoryGroup_Id,
                    'warehouse_Id' => $requestpayload['sender_warehouse_id'],
                    'transaction_Item_Id' => $item['transfer_item_id'],
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $unit,
                    'transaction_Qty' => $item['received_item_qty'],
                    'transaction_Item_OnHand' => (float)$sender_warehouse->item_OnHand - $item['received_item_qty'],
                    'transaction_Item_ListCost' => (float)$item['transfer_item_unit_cost'],
                    'transaction_UserID' =>  Auth::user()->idnumber,
                    'createdBy' =>  Auth::user()->idnumber,
                    'transaction_Acctg_TransType' =>  $transaction1->transaction_code ?? '',
                ]);

                $sequence1->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                ]);

              
               
                
            }

            $checkqty = '5';
            if($total_transfer_qty === $total_received_qty){
                $checkqty = '13';
            }
            $receivestocks->update([
                'received_by' => Auth::user()->idnumber, 
                'received_date' => Carbon::now(), 
                'status' => $checkqty,
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            
            (new RecomputePrice())->compute($receiver['warehouse_Id'],'',$receiver['item_Id'],'in');
            (new RecomputePrice())->compute($requestpayload['sender_warehouse_id'],'',$item['transfer_item_id'],'in');
            return response()->json(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function destroy(StockTransfer $stock_transfer)
    {
        if($stock_transfer->status == 1003) return response()->json(['message' => 'Transfer is already received'], 200);
        return $stock_transfer->delete();
    }
}
