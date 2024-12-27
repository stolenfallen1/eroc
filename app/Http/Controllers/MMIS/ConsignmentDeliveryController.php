<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\RecomputePrice;
use App\Models\BuildFile\Vendors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\Consignment;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Helpers\SearchFilter\inventory\Consignments;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;
use App\Helpers\SearchFilter\inventory\PurchaseOrderConsignments;

class ConsignmentDeliveryController extends Controller
{
    public function index()
    {
        if(Request()->tab == 3){
            return (new Consignments)->searchable();
        }else if( Request()->tab == 4 || Request()->tab == 5){
            return (new PurchaseOrderConsignments)->searchable();
        }
       
    }

    public function auditconsignment()
    {
        return (new PurchaseOrderConsignments)->auditsearchable();
    }
    
    public function auditedconsignment()
    {
        return (new PurchaseOrderConsignments)->auditedsearchable();
    }
    
    public function updatePOconsignment()
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            if(Request()->payload){
                $payload = Request()->payload;
                $has_dup_invoice_no = PurchaseOrderConsignment::where('po_id',$payload['po_id'])->where('invoice_no', $payload['invoice_no'])->exists();
                if($has_dup_invoice_no) return response()->json(['error' => 'Invoice already exist'], 200);
                PurchaseOrderConsignment::where('id',$payload['id'])->update([
                    'invoice_no'=>$payload['invoice_no'],
                    'invoice_date'=>$payload['invoice_date'],
                    'receivedDate'=>$payload['receivedDate']
                ]);
                DB::connection('sqlsrv_mmis')->commit();
                return response()->json(['message' => 'success'], 200);
            }
        }
        catch (\Exception $e) {
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }


    
    public function list()
    {
        $data = Consignment::with('status', 'vendor', 'warehouse','items','items.item','items.batchs')->whereNull('receivedstatus')->get();
        return response()->json($data,200);
    }
    
    public function store(Request $request){
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {

            $has_dup_invoice_no = Consignment::where('rr_Document_Delivery_Receipt_No', 'like', '%'.$request['rr_Document_Delivery_Receipt_No'].'%')->where('rr_Document_Vendor_Id',$request['rr_Document_Vendor_Id'])->exists();
            $vendor = Vendors::with('term')->findOrFail($request['rr_Document_Vendor_Id']);
            if($has_dup_invoice_no) return response()->json(['error' => 'Invoice already exist'], 200);
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'DSN1'])->first();
            if(!$sequence) return response()->json(['error' => 'No sequence found'], 200);
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffix = $sequence->seq_suffix;

            $department = $request['department_id'] ?? Auth::user()->warehouse_id;
            $rr_Document_TotalGrossAmount = 0;
            $rr_Document_TotalDiscountAmount = 0;
            $rr_Document_TotalNetAmount = 0;
            $rr_Document_Vat_Inclusive_Rate = 0;
            if(sizeof($request['items']) > 0){
                foreach ($request['items'] as $item) {
                    $itemDetails = Itemmasters::findOrfail($item['rr_Detail_Item_Id']);
                    $discount_amount = 0;
                    $vat_amount = 0;
                    $total_amount = $item['rr_Detail_Item_ListCost'] * $item['rr_Detail_Item_Qty_Received'];

                    if($item['rr_Detail_Item_TotalDiscount_Percent']){
                        $discount_amount = $total_amount * ($item['rr_Detail_Item_TotalDiscount_Percent'] / 100);
                    }
                    if($item['rr_Detail_Item_Vat_Rate']){
                        // if($itemDetails->isVatable == 1 || $itemDetails->isVatable != null){
                            $vat_amount = ($total_amount - $discount_amount) * ($item['rr_Detail_Item_Vat_Rate'] / 100);
                        // }
                    }
                    
                    $item_total_amount =($total_amount - $discount_amount) + $vat_amount;
                    $rr_Document_TotalGrossAmount += round($total_amount, 4);
                    $rr_Document_TotalDiscountAmount += round($discount_amount, 4);
                    $rr_Document_TotalNetAmount += round($item_total_amount, 4);
                    $rr_Document_Vat_Inclusive_Rate += round($vat_amount, 4);
                   
                }
            }

            $delivery = Consignment::create([
                'rr_Document_Number' => $number,
                'rr_Document_Prefix' => $prefix,
                'rr_Document_Suffix' => $suffix,
                'rr_Document_Barcode' => NULL,
                'rr_Document_Transaction_Date' => Carbon::now(),
                'rr_Document_Vendor_Id' => $vendor->id,
                'rr_Document_Delivery_Receipt_No' => $request['rr_Document_Delivery_Receipt_No'],
                'rr_Document_Invoice_Date' => $request['rr_Document_Invoice_Date'],
                'rr_Document_Delivery_Date' => $request['rr_Document_Delivery_Date'],
                'rr_Document_Terms_Id' => $vendor['term'] ? $vendor['term']['id'] : '',
                'rr_Document_TotalGrossAmount' => $rr_Document_TotalGrossAmount,
                'rr_Document_TotalDiscountAmount' => $rr_Document_TotalDiscountAmount,
                'rr_Document_TotalNetAmount' => $rr_Document_TotalNetAmount,
                'rr_Document_Vat_Inclusive_Rate' => $rr_Document_Vat_Inclusive_Rate,
                'rr_Document_Remarks' => $request['rr_Document_Remarks'],
                
                'rr_Document_Branch_Id' => Auth::user()->branch_id,
                'rr_Document_Warehouse_Group_Id' => Auth::user()->warehouse->warehouse_Group_Id,
                'rr_Document_Warehouse_Id' => $department,

                'po_Document_Number' => $request['po_Document_number'],
                'po_Document_Prefix' => $request['po_Document_prefix'],
                'po_Document_Suffix' => $request['po_Document_suffix'],
                'po_id' => $request['id'],
                'rr_Status' => $request['rr_Status'],
                'rr_received_by' => Auth::user()->idnumber,
                'rr_received_date' => $request['rr_received_date'],

                'category_id' => $request['category_id'],
                'item_group_id' => $request['item_group_id'],
                'isConsignment' => 1,
            ]);
            // return $delivery;

            foreach ($request['items'] as $key => $detail) {
                

                $itemDetails = Itemmasters::findOrfail($item['rr_Detail_Item_Id']);
                $discount_amount = 0;
                $vat_amount = 0;
                $total_amount = $item['rr_Detail_Item_ListCost'] * $item['rr_Detail_Item_Qty_Received'];

                if($item['rr_Detail_Item_TotalDiscount_Percent']){
                    $discount_amount = $total_amount * ($item['rr_Detail_Item_TotalDiscount_Percent'] / 100);
                }
                if($item['rr_Detail_Item_Vat_Rate']){
                    if($itemDetails->isVatable == 1 || $itemDetails->isVatable != null){
                        $vat_amount = ($total_amount - $discount_amount) * ($item['rr_Detail_Item_Vat_Rate'] / 100);
                    }
                }
                
                $item_total_amount =($total_amount - $discount_amount);
              

                $delivery_item = ConsignmentItems::create([
                    'rr_Document_Number' => $number,
                    'rr_Document_Prefix' => $prefix,
                    'rr_Document_Suffix' => $suffix,
                    'rr_id' => $delivery->id,
                    'rr_Detail_Item_Id' => $detail['rr_Detail_Item_Id'],
                    'rr_Detail_Item_ListCost' => $detail['rr_Detail_Item_ListCost'],
                    'rr_Detail_Item_Qty_Received' => $detail['rr_Detail_Item_Qty_Received'],
                    'rr_Detail_Item_UnitofMeasurement_Id_Received' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Received'],
                    'rr_Detail_Item_Qty_Convert' => $detail['rr_Detail_Item_Qty_Convert'] ?? NULL,
                    'rr_Detail_Item_UnitofMeasurement_Id_Convert' => $detail['rr_Detail_Item_UnitofMeasurement_Id_Convert'] ?? NULL,
                    'rr_Detail_Item_TotalGrossAmount' => $total_amount,
                    'rr_Detail_Item_TotalDiscount_Percent' => $detail['rr_Detail_Item_TotalDiscount_Percent'],
                    'rr_Detail_Item_TotalDiscount_Amount' => $discount_amount,
                    'rr_Detail_Item_TotalNetAmount' => $item_total_amount,
                    'rr_Detail_Item_Per_Box' => $detail['rr_Detail_Item_Per_Box'] ?? NULL,
                    'rr_Detail_Item_Vat_Rate' => $detail['rr_Detail_Item_Vat_Rate'] ?? NULL,
                    'rr_Detail_Item_Vat_Amount' => $vat_amount ?? NULL,
                    'createdBy' =>  Auth::user()->idnumber,
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
                        'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty'],
                        'item_ListCost' => (float)$delivery_item->rr_Detail_Item_ListCost,
                    ]);
                    
                    $batchdetails = ItemBatchModelMaster::create([
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

                    ConsignmentItems::where('rr_id',$delivery->id)->where('rr_Detail_Item_Id',$batch['item_Id'])->update([
                        'rr_Detail_Item_BatchNumber' => $batchdetails['id']
                    ]);
                    (new RecomputePrice())->compute($department,'',$batch['item_Id'],'out');
                    $sequence1 = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                    $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Received Stocks%')->where('isActive', 1)->first();

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
                    
                }

            }
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();

            // Log the error message and stack trace
            Log::error('Error processing delivery transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);

            // DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $payload = $request->payload;
            $department = $payload['department_id'] ?? Auth::user()->warehouse_id;

            $vendor = Vendors::with('term')->findOrFail($payload['rr_Document_Vendor_Id']);
            $delivery = Consignment::find($id);
            $delivery->where('id',$id)->update([
                'rr_Document_Vendor_Id' => $vendor->id,
                'rr_Document_Invoice_No' => $payload['rr_Document_Invoice_No'] ?? '',
                'rr_Document_Invoice_Date' => $payload['rr_Document_Invoice_Date'],
                'rr_Document_Delivery_Date' => $payload['rr_Document_Delivery_Date'],
                'rr_Document_Delivery_Receipt_No' => $payload['rr_Document_Delivery_Receipt_No'],
                'rr_Document_Delivery_Date' => $payload['rr_Document_Delivery_Date'],
                'rr_Document_TotalGrossAmount' => $payload['rr_Document_TotalGrossAmount'],
                'rr_Document_TotalDiscountAmount' => $payload['rr_Document_TotalDiscountAmount'],
                'rr_Document_TotalNetAmount' => $payload['rr_Document_TotalNetAmount'],
                'rr_Document_Remarks' => $payload['rr_Document_Remarks'],
                'rr_Document_Branch_Id' => Auth::user()->branch_id,
                'rr_Document_Warehouse_Group_Id' => Auth::user()->warehouse->warehouse_Group_Id,
                'rr_Document_Warehouse_Id' => $department,
                'rr_received_by' => Auth::user()->idnumber,
                'rr_received_date' => $payload['rr_received_date'],
                'category_id' => $payload['category_id'],
                'item_group_id' => $payload['item_group_id'],
                'isConsignment' => 1,
            ]);

            foreach ($payload['items'] as $key => $detail) {
                
                $delivery_item = ConsignmentItems::where('rr_id',$delivery->id)->where('rr_Detail_Item_Id',$detail['rr_Detail_Item_Id'])->first();
                $delivery_item->update([
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
                    'createdBy' =>  Auth::user()->idnumber,
                ]);
                
                foreach ($detail['batches'] as $key1 => $batch) {
                    
                    $warehouse_item = Warehouseitems::where([
                        'branch_id' => $delivery->rr_Document_Branch_Id,
                        'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                        'item_Id' => $batch['item_Id'],
                    ])->first();

                    if(!$warehouse_item) return response()->json(['error' => 'Item id: ' . $batch['item_Id']. ' not found in your location'], 200);
                    
                    $warehouse_item->update([
                        'item_OnHand' => (float)$warehouse_item->item_OnHand + (float)$batch['item_Qty'],
                        'item_ListCost' => (float)$delivery_item->rr_Detail_Item_ListCost,
                    ]);
                    
                    $batchdetails = ItemBatchModelMaster::where('item_Id', $batch['item_Id'])->where('warehouse_id', $warehouse_item->warehouse_Id)->first();     
                   
                   
                 
                    if($batchdetails){
                        $batchdetails->update([
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
                        ConsignmentItems::where('rr_id',$id)->where('rr_Detail_Item_Id',$batch['item_Id'])->update([
                            'rr_Detail_Item_BatchNumber' => $batchdetails['id']
                        ]);
                        (new RecomputePrice())->compute($department,'',$batch['item_Id'],'out');
                        $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();

                        // return $detail['purchase_request_detail'];
                        InventoryTransaction::where('transaction_Item_Batch_Detail', $batchdetails['id'])->where('transaction_Item_Id',$batch['item_Id'])->update([
                            'branch_Id' => $delivery->rr_Document_Branch_Id,
                            'warehouse_Group_Id' => $delivery->rr_Document_Warehouse_Group_Id,
                            'warehouse_Id' => $delivery->rr_Document_Warehouse_Id,
                            'transaction_Item_Id' =>  $batch['item_Id'],
                            'transaction_Item_Batch_Detail' => $batchdetails['id'],
                            'transaction_Date' => Carbon::now(),
                            'transaction_Item_UnitofMeasurement_Id' => $batch['item_UnitofMeasurement_Id'],
                            'transaction_Qty' => $batch['item_Qty'],
                            'transaction_Item_OnHand' => $warehouse_item->item_OnHand + $batch['item_Qty'],
                            'transaction_Item_ListCost' => $delivery_item->rr_Detail_Item_ListCost,
                            'transaction_UserID' =>  Auth::user()->idnumber,
                            'createdBy' =>  Auth::user()->idnumber,
                            'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                        ]);
                    }
                  
                   
                    
                }
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

}
