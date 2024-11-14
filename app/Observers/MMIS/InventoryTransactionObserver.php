<?php

namespace App\Observers\MMIS;

use DB;
use Carbon\Carbon;
use App\Helpers\GetIP;
use App\Models\OldMMIS\ItemMaster;
use Illuminate\Support\Facades\Log;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\inventory\medsys\RRHeaderModel;
use App\Models\MMIS\inventory\medsys\InventoryStock;
use App\Models\MMIS\inventory\medsys\InventoryStockCard;

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
        // DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        try {
            if (Auth()->user()->branch_id == 1) {
                $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            } else {
                $sequence = SystemSequence::where('code', 'ITCR2')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            }
            $hostname = gethostname();
            $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
            $deliveryDetails = Delivery::with('items','warehouse','vendor')->findOrFail($delivery->id);
            $maxRecordNumber = RRHeaderModel::max('RecordNumber');
            $recordNumber = $maxRecordNumber ? $maxRecordNumber + 1 : 1;
            $rrheader = RRHeaderModel::updateOrCreate(
                [
                    'LocationId'            => $deliveryDetails['warehouse']['map_item_id'],
                    'SupplierID'            => $deliveryDetails['vendor']['map_item_id'],
                    'TransNum'              => $deliveryDetails['rr_Document_Number'],
                ],
                [
                    'RecordNumber'          => $recordNumber,
                    'LocationId'            => $deliveryDetails['warehouse']['map_item_id'],
                    'SupplierID'            => $deliveryDetails['vendor']['map_item_id'],
                    'TransNum'              => $deliveryDetails['rr_Document_Number'],
                    'TransDate'             => $deliveryDetails['rr_Document_Transaction_Date'],
                    'Status'                => 'P',
                    'PONumber'              => $deliveryDetails['po_Document_Number'],
                    'SummaryCode'           => 'PU',
                    'Remarks'               => '',
                    'RRNumber'              => $deliveryDetails['rr_Document_Number'],
                    'EmergencyPurchase'     => 0,
                    'CorrectionEntry'       => 0,
                    'RIVNumber'             => '',
                    'RIVDate'               => Carbon::now(),
                    'RequisitionerUserID'   => '',
                    // 'DeliveryStatus'        => NULL,
                    'PORecordNumber'        => 0,
                    // 'TransType'             => NULL,
                    'PU_ID'                 => 1,
                    // 'Inserted'              => NULL,
                    'InvoiceDate'           => $deliveryDetails['rr_Document_Invoice_Date'],
                    'PODate'                => Carbon::now(),
                    'TermsID'               => 0,
                    'OldRecordNumber'       => 0,
                    'Purchaser'             => '',
                    'MultiPORecNum'         => 0,
                    // 'docnum'                => NULL,
                    'InvoiceAmount'         => $deliveryDetails['rr_Document_TotalNetAmount'],
                    'DeliveryDate'          => $deliveryDetails['rr_Document_Transaction_Date'],
                    // 'Voucher'               => NULL,
                    // 'InvoiceDiscount'       => NULL,
                    'DeliveryNum'           => '',
                ]
            );


            foreach ($deliveryDetails->items as $item) {

                $warehouse_item = Warehouseitems::with('itemMaster', 'warehouse')->where('branch_id', $delivery->rr_Document_Branch_Id)->where('item_Id', $item['rr_Detail_Item_Id'])->where('warehouse_Id', $delivery->rr_Document_Warehouse_Id)->first();
                $batch = ItemBatchModelMaster::with('item')->where('item_Id', $item['rr_Detail_Item_Id'])->where('delivery_item_id', $item['id'])->where('warehouse_Id', $delivery->rr_Document_Warehouse_Id)->first();
                $itemdetails = Itemmasters::where('id', $item['rr_Detail_Item_Id'])->first();

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
                    'transaction_Item_Model_Number'         => $batch->batch_Number,
                    'transaction_Item_Serial_Number'        => $batch->model_SerialNumber,
                    'transaction_Item_Expiry_Date'          => $batch->item_Expiry_Date,
                    'trasanction_Acctg_Revenue_Code'        => $transaction->code ?? '',
                    'transaction_Acctg_Item_Discount'       => null,
                    'transaction_Acctg_Account_Code'        => null,
                    'transaction_Acctg_Discount_Code'       => null,
                    'transaction_Acctg_CutOffDate'          => null,
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


                $rrheader->details()->updateOrCreate(
                    [
                        'RecordNumber'      => $rrheader['RecordNumber'],
                        'itemID'            => $itemdetails['map_item_id'],
                    ],
                    [
                        'RecordNumber'      => $rrheader['RecordNumber'],
                        'itemID'            => $itemdetails['map_item_id'],
                        'ListCost'          => $item['rr_Detail_Item_ListCost'],
                        'Quantity'          => $item['rr_Detail_Item_Qty_Received'],
                        'Packing'           => 1,
                        'LotNumber'         => $batch->batch_Number,
                        'Discount'          => $item['rr_Detail_Item_TotalDiscount_Amount'],
                        'Deal1'             => 0,
                        'Deal2'             => 0,
                        'ExpirationDate'    => $batch->item_Expiry_Date,
                        'NetCost'           => $item['rr_Detail_Item_ListCost'],
                        'QtyOrdered'        => $item['rr_Detail_Item_Qty_Received'],
                        'BackOrder'         => $item['rr_Detail_Item_Qty_BackOrder'],
                        'TotalNetCost'      => $item['rr_Detail_Item_TotalNetAmount'],
                        'VAT'               => $item['rr_Detail_Item_Vat_Amount'],
                        'AverageCost'       => $item['rr_Detail_Item_ListCost'],
                        'FreeGoods'         => $item['isFreeGoods'],
                        'Donation'          => 0,
                        'Regular'           => 0,
                        // 'PORecordNumber'    =>$deliveryDetails['po_Document_Number'],
                        'Inserted'          => 0,
                        'isADS'             => 0,
                        'PONumber'          => $deliveryDetails['po_Document_Number'],
                        'AddDiscount'       => 0,
                        'GrossAmount'       => $item['rr_Detail_Item_TotalGrossAmount'],
                        'isConvertPackage'  => 0,
                        'isConvertKit'      => 0,
                        'isUpdateAllLoc'    => 0,
                        'POSequenceNumber'  => 0,
                        'SequenceNumber'    => 0,
                        'isTrans'           => 0,
                        'VatInclusive'      => NULL,
                        'PriceStatus'       => NULL
                    ]
                );


                $rrheader->stockCard()->updateOrCreate(
                    [
                        'RRecordNumber'      => $rrheader['RecordNumber'],
                        'itemID'            => $itemdetails['map_item_id'],
                    ],
                    [
                        'RRecordNumber'     => $rrheader['RecordNumber'],
                        'SummaryCode'       => 'PU',
                        'itemID'            => $itemdetails['map_item_id'],
                        'TransDate'         => $deliveryDetails['rr_Document_Transaction_Date'],
                        'RefNum'            => $deliveryDetails['rr_Document_Number'],
                        'Quantity'          => $item['rr_Detail_Item_Qty_Received'],
                        'NetCost'           => $item['rr_Detail_Item_ListCost'],
                        'UserID'            => Auth()->user()->idnumber,
                        'ItemType'          => 'P',
                        'LocationID'        => $warehouse_item['warehouse']['map_item_id'],
                        'TargetLocationID'  => $deliveryDetails['vendor']['map_item_id'],
                        'LotNumber'         => $batch->batch_Number,
                        'Packing'           => 1,
                        // 'SequenceNumber'    => $item['SequenceNumber'],
                        'HemoRequest'       => 0,
                        'isADS'             => 0,
                        'HostName'          => $hostname,
                    ]
                );


                InventoryStock::where('LocationID',$warehouse_item['warehouse']['map_item_id'])->where('ItemID',$itemdetails['map_item_id'])->update(
                    [
                        'Markup_Out'=> $warehouse_item['item_Markup_Out'],
                        'Markup_In'=> $warehouse_item['item_Markup_In'],
                        'OnHand'=> $warehouse_item['item_OnHand'],
                        'SellingPriceOut'=> $warehouse_item['item_Selling_Price_Out'],
                        'SellingPriceIn'=> $warehouse_item['item_Selling_Price_In'],
                        'AverageCost'=> $warehouse_item['item_AverageCost'],
                        'ComputedOut'=> $warehouse_item['item_Selling_Price_Out'],
                        'ComputedIn'=> $warehouse_item['item_Selling_Price_In'],
                    ]
                );
            }


            // DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_inventory')->commit();
        } catch (\Exception $e) {
            // Log the error message and stack trace
            Log::error('Error processing delivery transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);

            // DB::connection('sqlsrv_mmis')->rollback();
            DB::connection('sqlsrv_medsys_inventory')->rollback();
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
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        try {
            if (Auth()->user()->branch_id == 1) {
                $sequence = SystemSequence::where('code', 'ITCR1')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            } else {
                $sequence = SystemSequence::where('code', 'ITCR2')->where('branch_id', Auth()->user()->branch_id)->first(); // for inventory transaction only
            }

            $hostname = gethostname();
            $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Purchased Items%')->where('isActive', 1)->first();
            $deliveryDetails = Delivery::with('items', 'warehouse','vendor')->findOrFail($delivery->id);
            $maxRecordNumber = RRHeaderModel::max('RecordNumber');
            $recordNumber = $maxRecordNumber ? $maxRecordNumber + 1 : 1;
            // $rrheader = RRHeaderModel::updateOrCreate(
            //     [
            //         'LocationId'            => $deliveryDetails['warehouse']['map_item_id'],
            //         'SupplierID'            => $deliveryDetails['vendor']['map_item_id'],
            //         'TransNum'              => $deliveryDetails['rr_Document_Number'],
            //     ],
            //     [
            //         'RecordNumber'          => $recordNumber,
            //         'LocationId'            => $deliveryDetails['warehouse']['map_item_id'],
            //         'SupplierID'            => $deliveryDetails['vendor']['map_item_id'],
            //         'TransNum'              => $deliveryDetails['rr_Document_Number'],
            //         'TransDate'             => $deliveryDetails['rr_Document_Transaction_Date'],
            //         'Status'                => 'P',
            //         'PONumber'              => $deliveryDetails['po_Document_Number'],
            //         'SummaryCode'           => 'PU',
            //         'Remarks'               => '',
            //         'RRNumber'              => $deliveryDetails['rr_Document_Number'],
            //         'EmergencyPurchase'     => 0,
            //         'CorrectionEntry'       => 0,
            //         'RIVNumber'             => '',
            //         'RIVDate'               => Carbon::now(),
            //         'RequisitionerUserID'   => '',
            //         // 'DeliveryStatus'        => NULL,
            //         'PORecordNumber'        => 0,
            //         // 'TransType'             => NULL,
            //         'PU_ID'                 => 1,
            //         // 'Inserted'              => NULL,
            //         'InvoiceDate'           => $deliveryDetails['rr_Document_Invoice_Date'],
            //         'PODate'                => Carbon::now(),
            //         'TermsID'               => 0,
            //         'OldRecordNumber'       => 0,
            //         'Purchaser'             => '',
            //         'MultiPORecNum'         => 0,
            //         // 'docnum'                => NULL,
            //         'InvoiceAmount'         => $deliveryDetails['rr_Document_TotalNetAmount'],
            //         'DeliveryDate'          => $deliveryDetails['rr_Document_Transaction_Date'],
            //         // 'Voucher'               => NULL,
            //         // 'InvoiceDiscount'       => NULL,
            //         'DeliveryNum'           => '',
            //     ]
            // );
            foreach ($deliveryDetails->items as $item) {

                $warehouse_item = Warehouseitems::with('itemMaster', 'warehouse')->where('branch_id', $delivery->rr_Document_Branch_Id)->where('item_Id', $item['rr_Detail_Item_Id'])->where('warehouse_Id', $delivery->rr_Document_Warehouse_Id)->first();
                $itemdetails = Itemmasters::where('id', $item['rr_Detail_Item_Id'])->first();
                $batch = ItemBatchModelMaster::with('item')->where('item_Id', $item['rr_Detail_Item_Id'])->where('delivery_item_id', $item['id'])->where('warehouse_Id', $delivery->rr_Document_Warehouse_Id)->first();


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
                    'transaction_Item_Model_Number'         => $batch->batch_Number,
                    'transaction_Item_Serial_Number'        => $batch->model_SerialNumber,
                    'transaction_Item_Expiry_Date'          => $batch->item_Expiry_Date,
                    'trasanction_Acctg_Revenue_Code'        => $transaction->code ?? '',
                    'transaction_Acctg_Item_Discount'       => null,
                    'transaction_Acctg_Account_Code'        => null,
                    'transaction_Acctg_Discount_Code'       => null,
                    'transaction_Acctg_CutOffDate'          => null,
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


                // $rrheader->details()->updateOrCreate(
                //     [
                //         'RecordNumber'      => $rrheader['RecordNumber'],
                //         'itemID'            => $itemdetails['map_item_id'],
                //     ],
                //     [
                //         'RecordNumber'      => $rrheader['RecordNumber'],
                //         'itemID'            => $itemdetails['map_item_id'],
                //         'ListCost'          => $item['rr_Detail_Item_ListCost'],
                //         'Quantity'          => $item['rr_Detail_Item_Qty_Received'],
                //         'Packing'           => 1,
                //         'LotNumber'         => $batch->batch_Number,
                //         'Discount'          => $item['rr_Detail_Item_TotalDiscount_Amount'],
                //         'Deal1'             => 0,
                //         'Deal2'             => 0,
                //         'ExpirationDate'    => $batch->item_Expiry_Date,
                //         'NetCost'           => $item['rr_Detail_Item_ListCost'],
                //         'QtyOrdered'        => $item['rr_Detail_Item_Qty_Received'],
                //         'BackOrder'         => $item['rr_Detail_Item_Qty_BackOrder'],
                //         'TotalNetCost'      => $item['rr_Detail_Item_TotalNetAmount'],
                //         'VAT'               => $item['rr_Detail_Item_Vat_Amount'],
                //         'AverageCost'       => $item['rr_Detail_Item_ListCost'],
                //         'FreeGoods'         => $item['isFreeGoods'],
                //         'Donation'          => 0,
                //         'Regular'           => 0,
                //         'PORecordNumber'    => $deliveryDetails['po_Document_Number'],
                //         'Inserted'          => 0,
                //         'isADS'             => 0,
                //         'PONumber'          => $deliveryDetails['po_Document_Number'],
                //         'AddDiscount'       => 0,
                //         'GrossAmount'       => $item['rr_Detail_Item_TotalGrossAmount'],
                //         'isConvertPackage'  => 0,
                //         'isConvertKit'      => 0,
                //         'isUpdateAllLoc'    => 0,
                //         'POSequenceNumber'  => 0,
                //         'SequenceNumber'    => 0,
                //         'isTrans'           => 0,
                //         'VatInclusive'      => NULL,
                //         'PriceStatus'       => NULL
                //     ]
                // );

                // $rrheader->stockCard()->updateOrCreate(
                //     [
                //         'RRecordNumber'      => $rrheader['RecordNumber'],
                //         'LocationID'        => $warehouse_item['warehouse']['map_item_id'],
                //         'itemID'            => $itemdetails['map_item_id'],
                //     ],
                //     [
                //         'RRecordNumber'     => $rrheader['RecordNumber'],
                //         'SummaryCode'       => 'PU',
                //         'itemID'            => $itemdetails['map_item_id'],
                //         'TransDate'         => $deliveryDetails['rr_Document_Transaction_Date'],
                //         'RefNum'            => $deliveryDetails['rr_Document_Number'],
                //         'Quantity'          => $item['rr_Detail_Item_Qty_Received'],
                //         'Balance'           => $warehouse_item['item_OnHand'],
                //         'NetCost'           => $item['rr_Detail_Item_ListCost'],
                //         'UserID'            => Auth()->user()->idnumber,
                //         'ItemType'          => 'P',
                //         'LocationID'        => $warehouse_item['warehouse']['map_item_id'],
                //         'TargetLocationID'  => $deliveryDetails['vendor']['map_item_id'],
                //         'LotNumber'         => $batch->batch_Number,
                //         'Packing'           => 1,
                //         'AverageCost'       => $warehouse_item['item_AverageCost'],
                //         'HemoRequest'       => 0,
                //         'isADS'             => 0,
                //         'HostName'          => $hostname,
                //     ]
                // );


                // InventoryStockCard::create(
                //     [
                //         'SummaryCode'       => 'AD',
                //         'itemID'            => $itemdetails['map_item_id'],
                //         'TransDate'         => $deliveryDetails['rr_Document_Transaction_Date'],
                //         'RefNum'            => '',
                //         'Quantity'          => $item['rr_Detail_Item_Qty_Received'],
                //         'Balance'           => $warehouse_item['item_OnHand'],
                //         'NetCost'           => $item['rr_Detail_Item_ListCost'],
                //         'UserID'            => Auth()->user()->idnumber,
                //         'ItemType'          => 'P',
                //         'LocationID'        => $warehouse_item['warehouse']['map_item_id'],
                //         'AverageCost'       => $warehouse_item['item_AverageCost'],
                //         'HostName'          => $hostname,
                //     ]
                // );
                // InventoryStock::where('LocationID',$warehouse_item['warehouse']['map_item_id'])->where('ItemID',$itemdetails['map_item_id'])->update(
                //     [
                //         'Markup_Out'=> $warehouse_item['item_Markup_Out'],
                //         'Markup_In'=> $warehouse_item['item_Markup_In'],
                //         'OnHand'=> $warehouse_item['item_OnHand'],
                //         'SellingPriceOut'=> $warehouse_item['item_Selling_Price_Out'],
                //         'SellingPriceIn'=> $warehouse_item['item_Selling_Price_In'],
                //         'AverageCost'=> $warehouse_item['item_AverageCost'],
                //         'ComputedOut'=> $warehouse_item['item_Selling_Price_Out'],
                //         'ComputedIn'=> $warehouse_item['item_Selling_Price_In'],
                //     ]
                // );

            }

            DB::connection('sqlsrv_medsys_inventory')->commit();
        } catch (\Exception $e) {
            // Log the error message and stack trace
            Log::error('Error processing delivery transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);

            DB::connection('sqlsrv_medsys_inventory')->rollback();
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
