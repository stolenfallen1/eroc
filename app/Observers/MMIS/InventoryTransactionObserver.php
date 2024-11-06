<?php

namespace App\Observers\MMIS;

use Carbon\Carbon;
use App\Helpers\GetIP;
use Illuminate\Support\Facades\Log;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\medsys\RRHeaderModel;
use DB;
class InventoryTransactionObserver
{
    /**
     * Handle the Delivery "created" event.
     *
     * @param  \App\Models\MMIS\inventory\Delivery  $delivery
     * @return void
     */
    public function created(Delivery $delivery)
    {
       DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            if (Auth()->user()->branch_id == 1) {
                $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            } else {
                $sequence = SystemSequence::where('code', 'ITCR2')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            }

            $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
            $deliveryDetails = Delivery::with('items')->findOrFail($delivery->id);

            // RRHeaderModel::updateOrCreate(
            //     [
            //         'LocationId'=>$deliveryDetails[''],
            //         'PONumber'=>$deliveryDetails[''],
            //         'RRNumber'=>$deliveryDetails[''],
            //     ],
            //     [
            //         'LocationId'=>$deliveryDetails[''],
            //         'SupplierID'=>$deliveryDetails[''],
            //         'TransNum'=>$deliveryDetails[''],
            //         'TransDate'=>$deliveryDetails[''],
            //         'Status'=>$deliveryDetails[''],
            //         'PONumber'=>$deliveryDetails[''],
            //         'SummaryCode'=>$deliveryDetails[''],
            //         'Remarks'=>$deliveryDetails[''],
            //         'RRNumber'=>$deliveryDetails[''],
            //         'EmergencyPurchase'=>$deliveryDetails[''],
            //         'CorrectionEntry'=>$deliveryDetails[''],
            //         'RIVNumber'=>$deliveryDetails[''],
            //         'RIVDate'=>$deliveryDetails[''],
            //         'RequisitionerUserID'=>$deliveryDetails[''],
            //         'DeliveryStatus'=>$deliveryDetails[''],
            //         'PORecordNumber'=>$deliveryDetails[''],
            //         'TransType'=>$deliveryDetails[''],
            //         'PU_ID'=>$deliveryDetails[''],
            //         'Inserted'=>$deliveryDetails[''],
            //         'InvoiceDate'=>$deliveryDetails[''],
            //         'PODate'=>$deliveryDetails[''],
            //         'TermsID'=>$deliveryDetails[''],
            //         'OldRecordNumber'=>$deliveryDetails[''],
            //         'Purchaser'=>$deliveryDetails[''],
            //         'MultiPORecNum'=>$deliveryDetails[''],
            //         'docnum'=>$deliveryDetails[''],
            //         'InvoiceAmount'=>$deliveryDetails[''],
            //         'DeliveryDate'=>$deliveryDetails[''],
            //         'Voucher'=>$deliveryDetails[''],
            //         'InvoiceDiscount'=>$deliveryDetails[''],
            //         'DeliveryNum'=>$deliveryDetails[''],
            //     ]
            // );

            foreach ($deliveryDetails->items as $item) {

                $warehouse_item = Warehouseitems::where('branch_id',$delivery->rr_Document_Branch_Id)->where('item_Id',$item['rr_Detail_Item_Id'])->where('warehouse_Id',$delivery->rr_Document_Warehouse_Id)->first();

                $batch = ItemBatchModelMaster::with('item')->where('item_Id',$item['rr_Detail_Item_Id'])->where('delivery_item_id',$item['id'])->where('warehouse_Id',$delivery->rr_Document_Warehouse_Id)->first();
               
               
                InventoryTransaction::create([
                    'branch_Id'                             => $delivery->rr_Document_Branch_Id,
                    'warehouse_Group_Id'                    => $delivery->rr_Document_Warehouse_Group_Id,
                    'warehouse_Id'                          => $delivery->rr_Document_Warehouse_Id,
                    'transaction_Item_Batch_Detail'         => $batch->id,
                    'transaction_Item_Id'                   => $item['rr_Detail_Item_Id'],
                    'transaction_Date'                      => Carbon::now(),
                    'trasanction_Reference_Number'          => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                    'transaction_Qty'                       => $batch->item_Qty,
                    'transaction_Item_OnHand'               => $warehouse_item->item_OnHand + $batch->item_Qty,
                    'transaction_Item_ListCost'             => $item['rr_Detail_Item_ListCost'],
                    'transaction_UserID'                    => Auth()->user()->idnumber,
                    'createdBy'                             => Auth()->user()->idnumber,
                    'updatedBy'                             => Auth()->user()->idnumber,
                    'transaction_Acctg_TransType'           => $transaction->code ?? '',
                    'transaction_Item_Barcode'              => $batch->item['item_Barcode'],
                    'transaction_Item_Model_Number'         => $batch->model_Number,
                    'transaction_Item_Serial_Number'        => $batch->model_SerialNumber,
                    'transaction_Item_Expiry_Date'          => $batch->item_Expiry_Date,
                    'trasanction_Acctg_Revenue_Code'        => $transaction->code ?? '',
                    'transaction_Acctg_Item_Discount'       => null,
                    'transaction_Acctg_Account_Code'        => null,
                    'transaction_Acctg_Discount_Code'       => null,
                    'transaction_Acctg_CutOffDate'          => null,
                    'transaction_Status'                    => null,
                    'transaction_Item_SellingAmount'        => $warehouse_item->item_Selling_Price_Out ?? '',
                    'transaction_Item_Discount'             => 0,
                    'transaction_Item_TotalAmount'          => 0,
                    'transaction_Item_Med_Dosage'           => $batch->item['item_Med_Dosage'],
                    'transaction_Item_Med_Frequency_Id'     => null,
                    'transaction_Target_Warhouse_Id'        => $delivery->rr_Document_Warehouse_Id,
                    'transaction_CreditMemo_Number'         => null,
                    'transaction_Requesting_Nurse'          => null,
                    'transaction_Requesting_Number'         => null,
                    'transaction_ORNumber'                  => null,
                    'transaction_Inventory_RR_Number'       => $delivery->rr_Document_Number,
                    'transaction_Inventory_RecordStatus'    => $delivery->rr_Status,
                    'transaction_Status'                    => $delivery->rr_Status,
                    'isFreeGoods'                           => $item['isFreeGoods'],
                    'transaction_Remarks'                   => $delivery['rr_Document_Remarks'],
                    'transaction_Hostname'                  => (new GetIP())->getHostname(),
                    'transaction_VeryBy'                    => null,
                    'transaction_count_by'                  => null,
                    'batch_id'                              => $batch->id,
                    'created_at'                            => Carbon::now(),
                ]);
            }
           

            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            // Log the error message and stack trace
            Log::error('Error processing delivery transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);

            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(['error' => 'An error occurred during processing.'], 500); // Optional: Return an error response
        }
    }

    /**
     * Handle the Delivery "updated" event.
     *
     * @param  \App\Models\MMIS\inventory\Delivery  $delivery
     * @return void
     */
    public function updated(Delivery $delivery)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            if (Auth()->user()->branch_id == 1) {
                $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            } else {
                $sequence = SystemSequence::where('code', 'ITCR2')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            }

            $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
            $deliveryDetails = Delivery::with('items')->findOrFail($delivery->id);
            foreach ($deliveryDetails->items as $item) {

                $warehouse_item = Warehouseitems::where('branch_id',$delivery->rr_Document_Branch_Id)->where('item_Id',$item['rr_Detail_Item_Id'])->where('warehouse_Id',$delivery->rr_Document_Warehouse_Id)->first();

                $batch = ItemBatchModelMaster::with('item')->where('item_Id',$item['rr_Detail_Item_Id'])->where('delivery_item_id',$item['id'])->where('warehouse_Id',$delivery->rr_Document_Warehouse_Id)->first();
               
               
                InventoryTransaction::create([
                    'branch_Id'                             => $delivery->rr_Document_Branch_Id,
                    'warehouse_Group_Id'                    => $delivery->rr_Document_Warehouse_Group_Id,
                    'warehouse_Id'                          => $delivery->rr_Document_Warehouse_Id,
                    'transaction_Item_Batch_Detail'         => $batch->id,
                    'transaction_Item_Id'                   => $item['rr_Detail_Item_Id'],
                    'transaction_Date'                      => Carbon::now(),
                    'trasanction_Reference_Number'          => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                    'transaction_Qty'                       => $batch->item_Qty,
                    'transaction_Item_OnHand'               => $warehouse_item->item_OnHand + $batch->item_Qty,
                    'transaction_Item_ListCost'             => $item['rr_Detail_Item_ListCost'],
                    'transaction_UserID'                    => Auth()->user()->idnumber,
                    'createdBy'                             => Auth()->user()->idnumber,
                    'transaction_Acctg_TransType'           => $transaction->code ?? '',
                    'transaction_Item_Barcode'              => $batch->item['item_Barcode'],
                    'transaction_Item_Model_Number'         => $batch->model_Number,
                    'transaction_Item_Serial_Number'        => $batch->model_SerialNumber,
                    'transaction_Item_Expiry_Date'          => $batch->item_Expiry_Date,
                    'trasanction_Acctg_Revenue_Code'        => $transaction->code ?? '',
                    'transaction_Acctg_Item_Discount'       => null,
                    'transaction_Acctg_Account_Code'        => null,
                    'transaction_Acctg_Discount_Code'       => null,
                    'transaction_Acctg_CutOffDate'          => null,
                    'transaction_Status'                    => null,
                    'transaction_Item_SellingAmount'        => $warehouse_item->item_Selling_Price_Out ?? '',
                    'transaction_Item_Discount'             => 0,
                    'transaction_Item_TotalAmount'          => 0,
                    'transaction_Item_Med_Dosage'           => $batch->item['item_Med_Dosage'],
                    'transaction_Item_Med_Frequency_Id'     => null,
                    'transaction_Target_Warhouse_Id'        => $delivery->rr_Document_Warehouse_Id,
                    'transaction_CreditMemo_Number'         => null,
                    'transaction_Requesting_Nurse'          => null,
                    'transaction_Requesting_Number'         => null,
                    'transaction_ORNumber'                  => null,
                    'transaction_Inventory_RR_Number'       => $delivery->rr_Document_Number,
                    'transaction_Inventory_RecordStatus'    => $delivery->rr_Status,
                    'transaction_Status'                    => $delivery->rr_Status,
                    'isFreeGoods'                           => $item['isFreeGoods'],
                    'transaction_Remarks'                   => $delivery['rr_Document_Remarks'],
                    'transaction_Hostname'                  => (new GetIP())->getHostname(),
                    'transaction_VeryBy'                    => null,
                    'transaction_count_by'                  => null,
                    'batch_id'                              => $batch->id,
                    'created_at'                            => Carbon::now(),
                ]);
            }
            
            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            // Log the error message and stack trace
            Log::error('Error processing delivery transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);

            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(['error' => 'An error occurred during processing.'], 500); // Optional: Return an error response
        }
    }

    /**
     * Handle the Delivery "deleted" event.
     *
     * @param  \App\Models\MMIS\inventory\Delivery  $delivery
     * @return void
     */
    public function deleted(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the Delivery "restored" event.
     *
     * @param  \App\Models\MMIS\inventory\Delivery  $delivery
     * @return void
     */
    public function restored(Delivery $delivery)
    {
        //
    }

    /**
     * Handle the Delivery "force deleted" event.
     *
     * @param  \App\Models\MMIS\inventory\Delivery  $delivery
     * @return void
     */
    public function forceDeleted(Delivery $delivery)
    {
        //
    }
}
