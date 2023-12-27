<?php

namespace App\Http\Controllers\POS\v1;

use Throwable;
use Carbon\Carbon;
use App\Models\POS\Orders;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\Customers;
use App\Models\POS\POSSettings;
use App\Models\POS\vwCustomers;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Support\Facades\DB;
use App\Models\POS\vwReturnDetails;
use App\Http\Controllers\Controller;
use App\Models\POS\vwPaymentReceipt;
use App\Models\POS\ReturnTransaction;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\vwPaymentReceiptItems;
use App\Helpers\PosSearchFilter\Orderlist;
use App\Helpers\PosSearchFilter\ReturnList;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\POS\ReturnDetailsTransaction;
use App\Models\MMIS\inventory\InventoryTransaction;

class NewReturnTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $orders_data = [];
    protected $item_details = [];
    protected $json_file = '';

    public function index()
    {
            $data = ReturnTransaction::query();
            $data->with('return_orders', 'orders')->orderBy('id', 'desc');
            if(Request()->type) {
                $data->where('refund_status_id',Request()->type);
            }
            if(Request()->keyword) {
                $data->where("refund_transaction_number","like","%".Request()->keyword."%");
            }
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
            
    }
     public function approvedreturnedorder(Request $request){
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $return = ReturnTransaction::where('id',$request->id)->update([
                'refund_status_id'=>'10'
            ]);
            foreach (Request()->return_items as $row) {
                
                $batch = ItemBatch::where('id', (int)$row['item_batch'])->first();
                $warehouseitem = Warehouseitems::where('item_Id',$row['id'])->where('branch_id', (int)Auth()->user()->branch_id)->first();
                if($batch) {
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $row['qty'];
                    if($usedqty >= $batch->item_Qty) {
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used +=$row['qty'];
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem) {
                    $warehouseitem->item_OnHand +=$row['qty'];
                    $warehouseitem->save();
                }

            }
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

        }
    }
    public function store(Request $request)
    {

        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('RTN', $terminal->terminal_code);
            $tran_sequenceno = (new SeriesNo())->get_sequence('RTN', $terminal->terminal_code);

            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            // order transaction
            $sequenceno = (new SeriesNo())->get_sequence('PPLN', $terminal->terminal_code);
            $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
            if($sequenceno->isSystem == '0') {
                $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
            }

            $returnorderid = Request()->order_payload['order_id'];
           
            $return = ReturnTransaction::create([
                'order_id' => $returnorderid,
                'returned_order_id' =>$returnorderid,
                'refund_transaction_number' => $generat_or_series,
                'refund_date' => Carbon::now(),
                'refund_method_id' => '1',
                'refund_amount' => Request()->return_payload['refund_amount'] ?? '',
                'refunded_to' => Request()->customer['id'] ?? '',
                'refund_reason' => Request()->order_payload['remarks'] ?? '',
                'refund_status_id' => '1',
                'report_date' => Carbon::now(),
                'terminal_id' => $terminal->id,
                'sales_batch_number' => Request()->order_payload['sales_batch_number'],
                'sales_batch_transaction_date' => Request()->order_payload['sales_batch_transaction_date'],
                'user_id' => Auth()->user()->idnumber,
                'shift_id' => Auth()->user()->shift,
                'createdBy' => Auth()->user()->idnumber,
            ]);

            foreach (Request()->return_items as $row) {
               
                    $return->refund_items()->create([
                      'returned_order_item_id' => $row['id'],
                      'returned_order_item_batchno' => $row['item_batch'],
                      'returned_order_item_qty' => $row['qty'],
                      'returned_order_item_charge_price' => $row['item_Selling_Price_In'],
                      'returned_order_item_cash_price' => $row['item_Selling_Price_Out'],
                      'returned_order_item_price' => $row['price'],
                      'returned_order_item_vat_rate' => $row['vat_rate'],
                      'returned_order_item_vat_amount' => $row['vat_rate'],
                      'returned_order_item_sepcial_discount' => 0,
                      'returned_order_item_total_amount' =>$row['totalamount'],
                      'returned_order_item_discount_amount' => $row['discount'],
                    
                      'order_item_id' => $row['id'],
                      'order_item_batchno' => $row['item_batch'],
                      'order_item_qty' => $row['qty'],
                      'order_item_charge_price' => $row['item_Selling_Price_In'],
                      'order_item_cash_price' => $row['item_Selling_Price_Out'],
                      'order_item_price' => $row['price'],
                      'order_item_vat_rate' => $row['vat_rate'],
                      'order_item_vat_amount' => $row['vat_rate'],
                      'order_item_sepcial_discount' => 0,
                      'order_item_total_amount' =>$row['totalamount'],
                      'order_item_discount_amount' => $row['discount'],
                      'createdBy' => Auth()->user()->idnumber,
                    ]);

            }

            $tran_sequenceno->update([
                'seq_no' => (int)$tran_sequenceno->seq_no + 1,
                'recent_generated' => $generate_trans_series,
            ]);
            $sequenceno->update([
                'seq_no' => (int)$sequenceno->seq_no + 1,
                'recent_generated' => $generatesequence,
            ]);

            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

        }

    }

}
