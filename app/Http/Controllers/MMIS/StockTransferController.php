<?php

namespace App\Http\Controllers\MMIS;

use App\Helpers\SearchFilter\inventory\StockTransfers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\StockTransfer;
use App\Models\MMIS\inventory\InventoryTransaction;

class StockTransferController extends Controller
{
    public function index()
    {
        return (new StockTransfers)->searchable();
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'ST1'])->first();
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            $delivery = Delivery::with('purchaseOrder')->where('id', $request->delivery_id)->first();
            $stock_transfer = StockTransfer::create([
                'sender_warehouse' => $authUser->warehouse_id, 
                'receiver_warehouse' => $request->warehouse_id,
                'transfer_by' => $authUser->idnumber, 
                'delivery_id' => $request->delivery_id, 
                'pr_id' => $delivery->purchaseOrder->pr_request_id,
                'po_id' => $delivery->purchaseOrder->id, 
                'status' => 1002,
                'document_number' => $number, 
                'document_prefix' => $prefix,
                'document_suffix' => $suffix
            ]);
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
            return response()->json(["error" => $e], 200);
        }
    }

    public function receiveTransfer(StockTransfer $stock_transfer)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Stock Transfer%')->where('isActive', 1)->first();
            $transaction1 = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Received Stocks%')->where('isActive', 1)->first();
            $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            $delivery = Delivery::with('items.batchs')->where('id', $stock_transfer->delivery_id)->first();
            foreach ($delivery->items as $item) {
                foreach ($item->batchs as $batch) {

                    $sender_warehouse = Warehouseitems::where([
                        'warehouse_Id' => $stock_transfer->sender_warehouse,
                        'item_Id' => $batch['item_Id'],
                    ])->first();
                        
                    $sender_warehouse->update([
                        'item_OnHand' => (float)$sender_warehouse->item_OnHand - (float)$batch['item_Qty']
                    ]);

                    $receiver_warehouse = Warehouseitems::where([
                        'warehouse_Id' => $stock_transfer->receiver_warehouse,
                        'item_Id' => $batch['item_Id'],
                    ])->first();

                    $receiver_warehouse->update([
                        'item_OnHand' => (float)$receiver_warehouse->item_OnHand + (float)$batch['item_Qty']
                    ]);


                    InventoryTransaction::create([
                        'branch_Id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Group_Id' => $delivery->rr_Document_Warehouse_Group_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'transaction_Item_Id' =>  $batch['item_Id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                        'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                        'transaction_Qty' => $batch['item_Qty'],
                        'transaction_Item_OnHand' => $receiver_warehouse->item_OnHand - $batch['item_Qty'],
                        'transaction_Item_ListCost' => $delivery->rr_Detail_Item_ListCost,
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                    ]);

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
                        'transaction_Item_ListCost' => $delivery->rr_Detail_Item_ListCost,
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

            $stock_transfer->update([
                'status' => 1003, 
                'received_by' => Auth::user()->idnumber
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

    public function destroy(StockTransfer $stock_transfer)
    {
        if($stock_transfer->status == 1003) return response()->json(['message' => 'Transfer is already received'], 200);
        return $stock_transfer->delete();
    }
}
