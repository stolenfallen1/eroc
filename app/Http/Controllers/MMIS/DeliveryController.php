<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\RecomputePrice;
use App\Models\BuildFile\Vendors;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\inventory\ItemModel;
use App\Models\MMIS\inventory\Consignment;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\DeliveryItems;
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Helpers\SearchFilter\inventory\Deliveries;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;

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
            $warehouse_id = $request['po_Document_warehouse_id'] ?? Auth::user()->warehouse_id;
            $has_dup_invoice_no = Delivery::where('rr_Document_Warehouse_Id', $warehouse_id)->where('po_Document_Number',$request['po_Document_number'])->where('rr_Document_Invoice_No', $request['rr_Document_Invoice_No'])->exists();
            if($has_dup_invoice_no) return response()->json(['error' => 'Invoice already exist'], 200);
            if(Auth::user()->branch_id == 1){
                $sequence = SystemSequence::where(['isActive' => true, 'code' => 'DSN1'])->where('branch_id', Auth::user()->branch_id)->first();
            }else{
                $sequence = SystemSequence::where(['isActive' => true, 'code' => 'DSN2'])->where('branch_id', Auth::user()->branch_id)->first();
            }
           
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;
            $overall_total_net = 0;
            $overall_discount_amount = 0;
            $overall_total_amount = 0;

            $delivery = Delivery::updateOrCreate(
                [
                    'rr_Document_Number' => $number,
                    'rr_Document_Branch_Id' => Auth::user()->branch_id
                ],
                [
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
                'rr_Document_Warehouse_Id' => $warehouse_id,

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

            $batch_seq = null;

            foreach ($request['details'] as $key => $detail) {
                $total_net = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_net_amount'];
                $total_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_total_amount'];
                $vat_rate = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_vat_rate'];
                $item_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_amount'];
                $discount_percent = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_discount_percent'];
                $discount_amount = $detail['purchase_request_detail']['recommended_canvas']['canvas_item_discount_amount'];
                
                if($delivery->rr_Status == 5 || $delivery->rr_Status == 11 || isset($detail['rr_Detail_Item_ListCost'])){
                    $item_amount = $detail['rr_Detail_Item_ListCost'] ?? $item_amount;
                    $total_amount = $item_amount * $detail['rr_Detail_Item_Qty_Received'];
                    if($vat_rate){
                        // if($request['vendor']['isVATInclusive'] == 0){
                            $vat_amount = $total_amount * ($vat_rate / 100);
                            $total_amount += $vat_amount;
                        // }
                    }
                    if($discount_percent){
                        $discount_amount = $total_amount * ($discount_percent / 100);
                        $overall_discount_amount += $discount_amount;
                    }
                    $total_net = $total_amount - $discount_amount;
                    $overall_total_net += $total_net;
                    $overall_total_amount += $total_amount;
                }
                
                $delivery_item = DeliveryItems::updateOrCreate(
                    [
                        'rr_id' => $delivery->id,
                        'rr_Detail_Item_Id' => $detail['item']['id'],
                    ],
                    [
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
                    'rr_Detail_Item_Vat_Rate' => $vat_rate,
                    'rr_Detail_Item_Vat_Amount' => $vat_amount ?? 0,
                ]);
                
                if($detail['item']['isLotNo_Required'] != "1" && $detail['rr_Detail_Item_Qty_Received'] > 0 ){
                    $batch_seq = SystemSequence::where('seq_description', 'like','%Receiving Batch NUmber%')->where('branch_id', Auth::user()->branch_id)->first();
                    
                    $batch_number = str_pad($batch_seq->seq_no, $batch_seq->digit, "0", STR_PAD_LEFT);
                    $batch_suffix = $batch_seq->seq_suffix;
                    $batch_prefix = $batch_seq->seq_prefix;
                    $generated_seq = generateCompleteSequence($batch_prefix, $batch_number, $batch_suffix, "");
                    
                    $detail['batches'] = [];
                    $detail['batches'][0]['item_Id'] = $detail['item']['id'];
                    $detail['batches'][0]['batch_Number'] = $generated_seq;
                    $detail['batches'][0]['batch_Remarks'] = 'auto generated';
                    $detail['batches'][0]['item_Qty'] = $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'] != 2 ? $detail['convert_qty'] : $detail['rr_Detail_Item_Qty_Received'];
                    $detail['batches'][0]['item_UnitofMeasurement_Id'] = 2;
                    $detail['batches'][0]['item_Expiry_Date'] = Carbon::now();
                    $detail['batches'][0]['mark_up'] = 0;

                }

                if(isset($detail['batches'])){

                    foreach ($detail['batches'] as $key1 => $batch) {
                        
                        $warehouse_item = Warehouseitems::where([
                        'branch_id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'item_Id' => $batch['item_Id'],
                        ])->first();
    
                        if(!$warehouse_item)
                            return response()->json(['error' => 'Item id: ' . $batch['item_Id']. ' not found in your location'], 200);
                        
                        $warehouse_item->update([
                            'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty']
                        ]);
                        $batchdetails = ItemBatchModelMaster::where('batch_Number',$batch['batch_Number'])->where('item_Id',$batch['item_Id'])->where('warehouse_id',$delivery->rr_Document_Warehouse_Id)->first();
                        $batchqty = 0;
                        if($batchdetails){
                            $batchqty = $batchdetails->item_Qty;
                        }
                        ItemBatchModelMaster::updateOrCreate(
                            [
                                'branch_id' => $delivery->rr_Document_Branch_Id,
                                'warehouse_id' => $delivery->rr_Document_Warehouse_Id,
                                'batch_Number' => $batch['batch_Number'],
                                'item_Id' => $batch['item_Id'],
                            ],
                            [
                            'branch_id' => $delivery->rr_Document_Branch_Id,
                            'warehouse_id' => $delivery->rr_Document_Warehouse_Id,
                            'batch_Number' => $batch['batch_Number'],
                            'batch_Transaction_Date' => Carbon::now(),
                            'batch_Remarks' => $batch['batch_Remarks'] ?? NULL,
                            'item_Id' => $batch['item_Id'],
                            'item_Qty' => (float) $batchqty  + $batch['item_Qty'],
                            'item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                            'item_Expiry_Date' => isset($batch['item_Expiry_Date']) ? Carbon::parse($batch['item_Expiry_Date']) : NULL,
                            'isConsumed' => 0,
                            'delivery_item_id' => $delivery_item->id,
                            'price' => $item_amount,
                            'mark_up' => $batch['mark_up'] ?? 0,
                        ]);
    
                        (new RecomputePrice())->compute($warehouse_id,'',$batch['item_Id'],'out');
    
                        if(Auth::user()->branch_id == 1){
                            $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                        }else{
                            $sequence1 = SystemSequence::where('code', 'ITCR2')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                        }
                        $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
    
                        // return $detail['purchase_request_detail'];
                        $batchdetail = ItemBatchModelMaster::where('batch_Number',$batch['batch_Number'])->where('item_Id',$batch['item_Id'])->where('warehouse_id',$delivery->rr_Document_Warehouse_Id)->first();
                        InventoryTransaction::create([
                            'branch_Id' => $delivery->rr_Document_Branch_Id,
                            'warehouse_Group_Id' => $delivery->rr_Document_Warehouse_Group_Id,
                            'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                            'transaction_Item_Batch_Detail' => $batchdetail['id'],
                            'transaction_Item_Id' =>  $batch['item_Id'],
                            'transaction_Date' => Carbon::now(),
                            'trasanction_Reference_Number' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                            'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                            'transaction_Qty' => $batch['item_Qty'],
                            'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $batch['item_Qty'],
                            'transaction_Item_ListCost' => $detail['purchase_request_detail']['recommended_canvas']['canvas_item_amount'],
                            'transaction_UserID' =>  Auth::user()->idnumber,
                            'createdBy' =>  Auth::user()->idnumber,
                            'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                        ]);
                        
                        $sequence1->update([
                            'seq_no' => (int) $sequence->seq_no + 1,
                            'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        ]);
    
                    }
                }

                if(isset($detail['free_goods'])){
                    foreach ($detail['free_goods'] as $key1 => $batch) {
                        
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
                        $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
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
                            'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                            'isFreeGoods' =>  "1",
                        ]);
                        
                        $sequence1->update([
                            'seq_no' => (int) $sequence->seq_no + 1,
                            'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                        ]);
                    }
                }
            }
            if($batch_seq != null){
                $batch_seq->update([
                    'seq_no' => (int) $batch_seq->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($batch_seq->seq_prefix, $batch_number, $batch_seq->seq_suffix, ''),
                ]);
            }

            if($delivery->rr_Status == 5 || $delivery->rr_Status == 11 || isset($detail['rr_Detail_Item_ListCost'])){
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
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function storeConsignment(Request $request){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $has_dup_invoice_no = Consignment::where('rr_Document_Invoice_No', 'like', '%'.$request['rr_Document_Invoice_No'].'%')->exists();
            $vendor = Vendors::with('term')->findOrFail($request['rr_Document_Vendor_Id']);
            if($has_dup_invoice_no) return response()->json(['error' => 'Invoice already exist'], 200);
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'DSN1'])->first();
            if(!$sequence) return response()->json(['error' => 'No sequence found'], 200);
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;

            $delivery = Consignment::create([
                'rr_Document_Number' => $number,
                'rr_Document_Prefix' => $prefix,
                'rr_Document_Suffix' => $suffix,
                'rr_Document_Barcode' => NULL,
                'rr_Document_Transaction_Date' => Carbon::now(),
                'rr_Document_Vendor_Id' => $vendor->id,
                'rr_Document_Invoice_No' => $request['rr_Document_Invoice_No'],
                'rr_Document_Delivery_Receipt_No' => $request['rr_Document_Invoice_No'],
                'rr_Document_Invoice_Date' => Carbon::now(),
                'rr_Document_Delivery_Date' => Carbon::now(),
                'rr_Document_Terms_Id' => $vendor['term']['id'],
                'rr_Document_TotalGrossAmount' => $request['rr_Document_TotalGrossAmount'],
                'rr_Document_TotalDiscountAmount' => $request['rr_Document_TotalDiscountAmount'],
                'rr_Document_TotalNetAmount' => $request['rr_Document_TotalNetAmount'],
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

                'category_id' => $request['category_id'],
                'item_group_id' => $request['item_group_id'],
                'isConsignment' => 1,
            ]);
            // return $delivery;

            foreach ($request['items'] as $key => $detail) {
                
                $delivery_item = ConsignmentItems::create([
                    'rr_id' => $delivery->id,
                    'rr_Detail_Item_Id' => $detail['rr_Detail_Item_Id'],
                    'rr_Detail_Item_ListCost' => $detail['rr_Detail_Item_ListCost'],
                    'rr_Detail_Item_Qty_Received' => $detail['rr_Detail_Item_Qty_Received'],
                    'rr_Detail_Item_UnitofMeasurement_Id_Received' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'],
                    'rr_Detail_Item_Qty_Convert' => $detail['rr_Detail_Item_Qty_Convert'] ?? NULL,
                    'rr_Detail_Item_UnitofMeasurement_Id_Convert' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Convert'] ?? NULL,
                    'rr_Detail_Item_TotalGrossAmount' => $detail['rr_Detail_Item_TotalGrossAmount'],
                    'rr_Detail_Item_TotalDiscount_Percent' => $detail['rr_Detail_Item_TotalDiscount_Percent'],
                    'rr_Detail_Item_TotalDiscount_Amount' => $detail['rr_Detail_Item_TotalDiscount_Amount'],
                    'rr_Detail_Item_TotalNetAmount' => $detail['rr_Detail_Item_TotalNetAmount'],
                    'rr_Detail_Item_Per_Box' => $detail['rr_Detail_Item_Per_Box'] ?? NULL,
                    'rr_Detail_Item_Vat_Rate' => $detail['rr_Detail_Item_Vat_Rate'] ?? NULL,
                    'rr_Detail_Item_Vat_Amount' => $detail['rr_Detail_Item_Vat_Amount'] ?? NULL,
                ]);
                
                foreach ($detail['batches'] as $key1 => $batch) {
                    
                    $warehouse_item = Warehouseitems::where([
                        'branch_id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'item_Id' => $batch['item_Id'],
                    ])->first();

                    if(!$warehouse_item)
                        return response()->json(['error' => 'Item id: ' . $batch['item_Id']. ' not found in your location'], 200);
                    
                    $warehouse_item->update([
                        'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty']
                    ]);

                    ItemBatchModelMaster::create([
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
                        'price' => $delivery_item->rr_Detail_Item_ListCost,
                        'mark_up' => $batch['mark_up'] ?? 0,
                        'isconsignment' => 1
                    ]);
                    
                    $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                    $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();

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
                        'transaction_Item_ListCost' => $delivery_item->rr_Detail_Item_ListCost,
                        'transaction_UserID' =>  Auth::user()->idnumber,
                        'createdBy' =>  Auth::user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                    ]);
                    
                    $sequence1->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($sequence1->seq_prefix, $sequence1->seq_no, $sequence1->seq_suffix, ''),
                    ]);

                }

               
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

    public function show(Request $request){
        $id = Request()->id;
        $po = Request()->po;
        $invoice = Request()->invoice;
        $delivery = Delivery::with(['warehouse', 'audit', 'items', 'receiver', 'purchaseOrder' => function($q1){
            $q1->with(['deliveryItems' => function($q2){
              $q2->with('delivery.audit.user', 'item', 'unit')->whereHas('delivery', function($q3){
                // $q3->whereHas('audit');
              });
            },'purchaseRequest' => function($q5){
              $q5->with(['itemGroup', 'user', 'category','purchaseRequestDetails'=>function($qq){
                $qq->with('itemMaster','unit');
              }]);
            }, 'comptroller', 'administrator', 'corporateAdmin', 'president', 'details' => function($q2){
              $q2->with(['purchaseRequestDetail' => function($q3){
                $q3->with(['purchaseRequest' => function($q4){
                  $q4->with('warehouse', 'itemGroup', 'user', 'category');
                }, 'itemMaster', 'unit', 'unit2', 'depApprovedBy', 'adminApprovedBy', 'conApprovedBy', 'recommendedCanvas']);
              }, 'canvas.vendor']);
            }]);
          }])->where('po_id',$po)->where('rr_Document_Invoice_No',$invoice)->first();
        return response()->json(['delivery' => $delivery]);
    }

    public function createConsignmentPr(Request $request){
        
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
