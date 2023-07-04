<?php

namespace App\Http\Controllers\MMIS;

use App\Helpers\SearchFilter\inventory\Deliveries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\DeliveryItems;
use App\Models\MMIS\inventory\InventoryTransaction;

class DeliveryController extends Controller
{
    public function index()
    {
        return (new Deliveries)->searchable();
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $has_dup_invoice_no = Delivery::where('rr_Document_Invoice_No', 'like', '%'.$request['rr_Document_Invoice_No'].'%')->exists();
            if($has_dup_invoice_no) return response()->json(['error' => 'Invoice already exist'], 200);
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'DSN1'])->first();
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            $overall_total_net = 0;
            $overall_discount_amount = 0;
            $overall_total_amount = 0;

            $delivery = Delivery::create([
                'rr_Document_Number' => $number,
                'rr_Document_Prefix' => $prefix,
                'rr_Document_Suffix' => $suffix,
                'rr_Document_Barcode' => NULL,
                'rr_Document_Transaction_Date' => Carbon::now(),
                'rr_Document_Expected_Delivery_Date' => Carbon::parse($request['po_Document_expected_deliverydate']),
                'rr_Document_Vendor_Id' => $request['po_Document_vendor_id'],
                'rr_Document_Invoice_No' => $request['rr_Document_Invoice_No'],
                'rr_Document_Invoice_Date' => Carbon::parse($request['rr_Document_Invoice_Date']),
                'rr_Document_Terms_Id' => $request['vendor']['term']['id'],
                'rr_Document_TotalGrossAmount' => $request['po_Document_total_gross_amount'],
                'rr_Document_Vat_Inclusive_Rate' => $request['po_Document_vat_percent'],
                'rr_Document_TotalDiscountAmount' => $request['po_Document_discount_amount'],
                'rr_Document_TotalNetAmount' => $request['po_Document_total_net_amount'],
                'rr_Document_Remarks' => $request['rr_Document_Remarks'],

                'rr_Document_Branch_Id' => Auth::user()->branch_id,
                'rr_Document_Warehouse_Group_Id' => Auth::user()->warehouse->warehouse_Group_Id,
                'rr_Document_Warehouse_Id' => Auth::user()->warehouse_id,

                'po_Document_Number' => $request['po_Document_number'],
                'po_Document_Prefix' => $request['po_Document_prefix'],
                'po_Document_Suffix' => $request['po_Document_suffix'],
                'po_id' => $request['id'],
                'rr_Status' => $request['rr_Status'],
                'rr_received_by' => Auth::user()->idnumber,
            ]);
            
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
            foreach ($request['details'] as $key => $detail) {
                $total_net = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_net_amount'];
                $total_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_total_amount'];
                $vat_rate = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_vat_rate'];
                $item_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_amount'];
                $discount_percent = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_discount_percent'];
                $discount_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_discount_amount'];
                
                // if($delivery->rr_Status == 5 && (Auth::user()->role->name != 'dietary' && Auth::user()->role->name != 'dietary head')){
                    //     $total_amount = $item_amount * $detail['rr_Detail_Item_Qty_Received'];
                    //     if($vat_rate){
                        //         if($request['vendor']['isVATInclusive'] == 0){
                            //             $vat_amount = $total_amount * ($vat_rate / 100);
                            //             $total_amount += $vat_amount;
                            //         }
                            //     }
                            //     if($discount_percent){
                                //         $discount_amount = $total_amount * ($discount_percent / 100);
                                //         $overall_discount_amount += $discount_amount;
                                //     }
                                //     $total_net = $total_amount - $discount_amount;
                //     $overall_total_net += $total_net;
                //     $overall_total_amount += $total_amount;
                // }
                
                if($delivery->rr_Status == 5 || isset($detail['rr_Detail_Item_ListCost'])){
                    $item_amount = $detail['rr_Detail_Item_ListCost'] ?? $item_amount;
                    $total_amount = $item_amount * $detail['rr_Detail_Item_Qty_Received'];
                    if($vat_rate){
                        if($request['vendor']['isVATInclusive'] == 0){
                            $vat_amount = $total_amount * ($vat_rate / 100);
                            $total_amount += $vat_amount;
                        }
                    }
                    if($discount_percent){
                        $discount_amount = $total_amount * ($discount_percent / 100);
                        $overall_discount_amount += $discount_amount;
                    }
                    $total_net = $total_amount - $discount_amount;
                    $overall_total_net += $total_net;
                    $overall_total_amount += $total_amount;
                }
                
                $delivery_item = DeliveryItems::create([
                    'rr_id' => $delivery->id,
                    'rr_Detail_Item_Id' => $detail['item']['id'],
                    'rr_Detail_Item_ListCost' => $item_amount,
                    'rr_Detail_Item_Qty_Received' => $detail['rr_Detail_Item_Qty_Received'],
                    'rr_Detail_Item_UnitofMeasurement_Id_Received' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'],
                    'rr_Detail_Item_Qty_Convert' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'] != 2 ? $detail['convert_qty'] : NULL,
                    'rr_Detail_Item_UnitofMeasurement_Id_Convert' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'] != 2 ? $detail['convert_uom'] : NULL,
                    'rr_Detail_Item_Qty_BackOrder' => $detail['rr_Detail_Item_Qty_BackOrder'] ?? NULL,
                    'rr_Detail_Item_UnitofMeasurement_Id_BackOrder' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'],
                    'rr_Detail_Item_TotalGrossAmount' => $total_amount,
                    'rr_Detail_Item_TotalDiscount_Percent' => $discount_percent,
                    'rr_Detail_Item_TotalDiscount_Amount' => $discount_amount,
                    'rr_Detail_Item_TotalNetAmount' => $total_net,
                    'rr_Detail_Item_Per_Box' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'] != 2 ? $detail['rr_Detail_Item_Per_Box'] : NULL,
                ]);
                
                foreach ($detail['batches'] as $key1 => $batch) {
                    
                    $warehouse_item = Warehouseitems::where([
                    'branch_id' => $delivery->rr_Document_Branch_Id,
                    'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                    'item_Id' => $batch['item_Id'],
                    ])->first();
                    
                    $warehouse_item->update([
                        'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty']
                    ]);

                    ItemBatch::create([
                        'branch_id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_id' => $delivery->rr_Document_Warehouse_Id,
                        'batch_Number' => $batch['batch_Number'],
                        'batch_Transaction_Date' => Carbon::now(),
                        'batch_Remarks' => $batch['batch_Remarks'] ?? NULL,
                        'item_Id' => $batch['item_Id'],
                        'item_Qty' => $batch['item_Qty'],
                        'item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                        'item_Expiry_Date' => isset($batch['item_Expiry_Date']) ? Carbon::parse($batch['item_Expiry_Date']) : NULL,
                        'isConsumed' => 0,
                        'delivery_item_id' => $delivery_item->id,
                    ]);
                    
                    $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                    $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();

                    // return $detail['purchase_request_detail'];
                    InventoryTransaction::create([
                        'branch_Id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Group_Id' => $delivery->rr_Document_Warehouse_Group_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'transaction_Item_Id' =>  $batch['item_Id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                        'transaction_Qty' => $batch['item_Qty'],
                        'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $batch['item_Qty'],
                        'transaction_Item_ListCost' => $detail['purchase_request_detail']['recommended_canvas']['canvas_item_amount'],
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                    ]);
                    
                    $sequence1->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                    ]);

                }

                if(isset($detail['free_goods'])){
                    foreach ($detail['free_goods'] as $key1 => $batch) {
                        return "test";
                        
                        $warehouse_item = Warehouseitems::where([
                        'branch_id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'item_Id' => $batch['item_Id'],
                        ])->first();
                        
                        $warehouse_item->update([
                            'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty']
                        ]);
    
                        ItemBatch::create([
                            'branch_id' => $delivery->rr_Document_Branch_Id,
                            'warehouse_id' => $delivery->rr_Document_Warehouse_Id,
                            'batch_Number' => $batch['batch_Number'],
                            'batch_Transaction_Date' => Carbon::now(),
                            'batch_Remarks' => $batch['batch_Remarks'] ?? NULL,
                            'item_Id' => $batch['item_Id'],
                            'item_Qty' => $batch['item_Qty'],
                            'item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                            'item_Expiry_Date' => isset($batch['item_Expiry_Date']) ? Carbon::parse($batch['item_Expiry_Date']) : NULL,
                            'isConsumed' => 0,
                            'delivery_item_id' => $delivery_item->id,
                        ]);
                        
                        $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                        $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
                        // return $detail['purchase_request_detail'];
                        InventoryTransaction::create([
                            'branch_Id' => $delivery->rr_Document_Branch_Id,
                            'warehouse_Group_Id' => $delivery->rr_Document_Warehouse_Group_Id,
                            'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                            'transaction_Item_Id' =>  $batch['item_Id'],
                            'transaction_Date' => Carbon::now(),
                            'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                            'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                            'transaction_Qty' => $batch['item_Qty'],
                            'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $batch['item_Qty'],
                            'transaction_Item_ListCost' => $detail['purchase_request_detail']['recommended_canvas']['canvas_item_amount'],
                            'transaction_UserID' =>  Auth::user()->idnumber,
                            'createdBy' =>  Auth::user()->idnumber,
                            'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                            'isFreeGoods' =>  "1",
                        ]);
                        
                        $sequence1->update([
                            'seq_no' => (int) $sequence->seq_no + 1,
                            'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        ]);
                    }
                }
            }
            if($delivery->rr_Status == 5 || isset($detail['rr_Detail_Item_ListCost'])){
                $delivery->update([
                    'rr_Document_TotalDiscountAmount' => $overall_discount_amount,
                    'rr_Document_TotalNetAmount' => $overall_total_net,
                    'rr_Document_TotalGrossAmount' => $overall_total_amount
                ]);
            }

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function warehouseDelivery($id){
        try {
            $deliveries = Delivery::where('rr_Document_Warehouse_Id',  Auth::user()->warehouse_id)
            ->where(function($query) use($id){
                $query->whereHas('purchaseOrder', function($q) use($id){
                    $q->whereHas('purchaseRequest', function($q1) use($id){
                        $q1->where('warehouse_Id', $id);
                    });
                });
            })
            ->whereDoesntHave('stockTransfer')
            ->get();
            return $deliveries;
        } catch (\Throwable $e) {
            return response()->json(['error' => $e], 200);
        }
    }

    public function update(Request $request, $id)
    {
        # code...
    }

    public function destroy($id)
    {
        # code...
    }
}
