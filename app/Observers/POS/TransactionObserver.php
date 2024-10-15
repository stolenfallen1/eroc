<?php

namespace App\Observers\POS;

use Carbon\Carbon;
use App\Helpers\GetIP;
use App\Models\POS\Payments;
use Illuminate\Support\Facades\Log;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use DB;
class TransactionObserver
{
    /**
     * Handle the Payments "created" event.
     *
     * @param  \App\Models\POS\Payments  $payments
     * @return void
     */
    public function created(Payments $payments)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $orders = $payments->orders;
            
            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
            $tran_sequenceno = (new SeriesNo())->get_sequence('PTN', $terminal->terminal_code);
            $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            
            $transaction = FmsTransactionCode::where('code', 'PY')->where('isActive', 1)->first();
        
            foreach ($orders->order_items as $item) {

                $warehouse = Warehouseitems::where("item_Id",$item['order_item_id'])->where('warehouse_Id',Auth()->user()->warehouse_id)->where('branch_id',Auth()->user()->branch_id)->first();
                $batch = ItemBatchModelMaster::where("id", $item['order_item_batchno'])->first();
               
                InventoryTransaction::create([
                    'branch_Id'                             => Auth()->user()->branch_id,
                    // 'warehouse_Group_Id'                    => $delivery->rr_Document_Warehouse_Group_Id,
                    'warehouse_Id'                          => Auth()->user()->warehouse_id,
                    'transaction_Item_Batch_Detail'         => $batch->id,
                    'transaction_Item_Id'                   => $item['order_item_id'],
                    'transaction_Date'                      => Carbon::now(),
                    'trasanction_Reference_Number'          => $generate_trans_series,
                    'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                    'transaction_Qty'                       => $item['order_item_qty'],
                    'transaction_Item_OnHand'               => $warehouse->item_OnHand,
                    'transaction_Item_ListCost'             => $warehouse->item_ListCost,
                    'transaction_UserID'                    => Auth()->user()->idnumber,
                    'createdBy'                             => Auth()->user()->idnumber,
                    'transaction_Acctg_TransType'           => $transaction->lgrp ?? '',
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
                    'transaction_Item_SellingAmount'        => $warehouse->item_Selling_Price_Out ?? '',
                    'transaction_Item_Discount'             => 0,
                    'transaction_Item_TotalAmount'          => 0,
                    'transaction_Item_Med_Dosage'           => null,
                    'transaction_Item_Med_Frequency_Id'     => null,
                    'transaction_Target_Warhouse_Id'        => Auth()->user()->warehouse_id,
                    'transaction_CreditMemo_Number'         => null,
                    'transaction_Requesting_Nurse'          => null,
                    'transaction_Requesting_Number'         => null,
                    'transaction_ORNumber'                  => $generate_or_series,
                    'transaction_Inventory_RR_Number'       => null,
                    'transaction_Inventory_RecordStatus'    => null,
                    'transaction_Status'                    => null,
                    'isFreeGoods'                           => 0,
                    'transaction_Remarks'                   => null,
                    'transaction_Hostname'                  => (new GetIP())->getHostname(),
                    'transaction_Terminal_Id'               => $terminal->id,
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
     * Handle the Payments "updated" event.
     *
     * @param  \App\Models\POS\Payments  $payments
     * @return void
     */
    public function updated(Payments $payments)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $orders = $payments->orders;
            
            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
            $tran_sequenceno = (new SeriesNo())->get_sequence('PTN', $terminal->terminal_code);
            $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            
            $transaction = FmsTransactionCode::where('code', 'PY')->where('isActive', 1)->first();
        
            foreach ($orders->order_items as $item) {

                $warehouse = Warehouseitems::where("item_Id",$item['order_item_id'])->where('warehouse_Id',Auth()->user()->warehouse_id)->where('branch_id',Auth()->user()->branch_id)->first();
                $batch = ItemBatchModelMaster::where("id", $item['order_item_batchno'])->first();
               
                InventoryTransaction::create([
                    'branch_Id'                             => Auth()->user()->branch_id,
                    // 'warehouse_Group_Id'                    => $delivery->rr_Document_Warehouse_Group_Id,
                    'warehouse_Id'                          => Auth()->user()->warehouse_id,
                    'transaction_Item_Batch_Detail'         => $batch->id,
                    'transaction_Item_Id'                   => $item['order_item_id'],
                    'transaction_Date'                      => Carbon::now(),
                    'trasanction_Reference_Number'          => $generate_trans_series,
                    'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                    'transaction_Qty'                       => $item['order_item_qty'],
                    'transaction_Item_OnHand'               => $warehouse->item_OnHand,
                    'transaction_Item_ListCost'             => $item['order_item_total_amount'],
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
                    'transaction_Item_SellingAmount'        => $warehouse->item_Selling_Price_Out ?? '',
                    'transaction_Item_Discount'             => 0,
                    'transaction_Item_TotalAmount'          => 0,
                    'transaction_Item_Med_Dosage'           => null,
                    'transaction_Item_Med_Frequency_Id'     => null,
                    'transaction_Target_Warhouse_Id'        => Auth()->user()->warehouse_id,
                    'transaction_CreditMemo_Number'         => null,
                    'transaction_Requesting_Nurse'          => null,
                    'transaction_Requesting_Number'         => null,
                    'transaction_ORNumber'                  => $generate_or_series,
                    'transaction_Inventory_RR_Number'       => null,
                    'transaction_Inventory_RecordStatus'    => null,
                    'transaction_Status'                    => null,
                    'isFreeGoods'                           => 0,
                    'transaction_Remarks'                   => null,
                    'transaction_Hostname'                  => (new GetIP())->getHostname(),
                    'transaction_Terminal_Id'               => $terminal->id,
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
     * Handle the Payments "deleted" event.
     *
     * @param  \App\Models\POS\Payments  $payments
     * @return void
     */
    public function deleted(Payments $payments)
    {
        //
    }

    /**
     * Handle the Payments "restored" event.
     *
     * @param  \App\Models\POS\Payments  $payments
     * @return void
     */
    public function restored(Payments $payments)
    {
        //
    }

    /**
     * Handle the Payments "force deleted" event.
     *
     * @param  \App\Models\POS\Payments  $payments
     * @return void
     */
    public function forceDeleted(Payments $payments)
    {
        //
    }
}
