<?php

namespace App\Http\Controllers\POS;

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

class ReturnTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $orders_data = [];
    protected $item_details = [];
    protected $json_file ='';


    public function get_order_transaction()
    {
        $data = vwPaymentReceipt::with('order_items')->where('sales_invoice_number',Request()->payload['transaction_number'])->first();
        return response()->json(["data"=>$data,"message" => 'success' ], 200);
    }

    public function index()
    {
        $data = (new ReturnList())->searchable();
        return response()->json(["data"=>$data,"message" => 'success' ], 200);
    }

    public function returnorder()
    {
       $data = (new Orderlist())->returnordersearchable();
        return response()->json(["data"=>$data,"message" => 'success' ], 200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function getRefundDetails(Request $request)
    {
       $data['order'] = vwReturnDetails::where('id',$request->refundid)->where('type','order')->get();
       $data['return'] = vwReturnDetails::where('id',$request->refundid)->where('type','return')->get();
       $data['message'] = 'success';
       return response()->json($data, 200);
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function return_approval(Request $request){

        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $return = ReturnTransaction::where('id',Request()->return_order_details['refund_id'])->first();
            $status = '1';
            if(Request()->return_order_details['returned_refund'] <= 0){
                $status = '11';
            }else {
                $status = '6';
            }
            
            if(Request()->return_order_details['action'] == false){
                $status = '4';
            }
          
           
            if(Request()->return_order_details['returned_refund'] == 0){
               
                $return_items = ReturnDetailsTransaction::where('refund_id',Request()->return_order_details['refund_id'])->get();
                foreach($return_items as $row){

                    $warehouseitem = DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id', (int)$row['returned_order_item_id'])->first();
                    $batch = DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['returned_order_item_batchno'])->where('item_Id', (int)$row['returned_order_item_id'])->first();
                    if($batch) {
                        $isConsumed = '0';
                        $usedqty = $batch->item_Qty_Used - $row['returned_order_item_qty'];
                        if($usedqty >= $batch->item_Qty) {
                            $isConsumed = '1';
                        }
                        DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['returned_order_item_batchno'])->update([
                            'item_Qty_Used'=>  (int)$batch->item_Qty_Used - (int)$row['returned_order_item_qty'],
                            'isConsumed'=>  $isConsumed 
                        ]);
                    }
                    DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id',(int)$row['returned_order_item_id'])->update([
                        'item_OnHand'=> (int)$warehouseitem->item_OnHand + (int)$row['returned_order_item_qty']
                    ]);
                }
                $return->update([
                    'refund_status_id'=>$status,
                    'approvedby'=>Auth()->user()->idnumber,
                    'approved_at'=>Carbon::now()
                ]);
            
            }else{
              
                $return->update([
                    'refund_status_id'=>$status,
                    'approvedby'=>Auth()->user()->idnumber,
                    'approved_at'=>Carbon::now()
                ]);
            }
          
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    public function return_item(Request $request){
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
      
            $terminal = (new Terminal)->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('RTN',$terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('RTN',$terminal->terminal_code);
            
            
            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0'){
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            // order transaction 
            $sequenceno = (new SeriesNo())->get_sequence('PPLN',$terminal->terminal_code);
            $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
            if($sequenceno->isSystem == '0'){
                $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
            }
            
            // $returnitem = vwPaymentReceiptItems::where('order_id',Request()->return_order_details['order_id'])->where('itemid',Request()->return_order_details['itemid'])->first();
            // $refund_amount = $returnitem->order_item_total_amount  - (Request()->item['itemcashprice'] * Request()->item['return_qty']);
            $returnorderid = Request()->return_order_details['order_id'];

          
            if(Request()->return_order_details['return_type'] =='exchanged'){
                $orders = Orders::create([
                    'branch_id'=> Auth()->user()->branch_id,
                    'warehouse_id'=> Auth()->user()->warehouse_id,
                    'customer_id'=>Request()->return_order_details['customer_id'] ?? '',
                    'pick_list_number'=> $generatesequence,
                    'order_date'=> Carbon::now(),
                    'order_total_line_item_ordered'=> Request()->order_computation['order_total_line_item_ordered'],
                    'order_vatable_sales_amount'=> Request()->order_computation['order_vatable_sales_amount'],
                    'order_vatexempt_sales_amount'=> Request()->order_computation['order_vatexempt_sales_amount'],
                    'order_zero_rated_sales_amount'=> Request()->order_computation['order_zero_rated_sales_amount'],
                    'order_total_sales_vat_incl_amount'=> Request()->order_computation['order_total_sales_vat_incl_amount'],
                    'order_vat_amount'=> Request()->order_computation['order_vat_amount'],
                    'order_vat_net_amount'=> Request()->order_computation['order_vat_net_amount'],
                    'order_senior_citizen_amount'=> Request()->order_computation['order_senior_citizen_amount'],
                    'order_due_amount'=> Request()->order_computation['order_due_amount'],
                    'order_total_payment_amount'=> Request()->order_computation['order_total_payment_amount'],
                    'pa_userid'=>Auth()->user()->idnumber,
                    'cashier_user_id'=>0,
                    'checker_userid'=>0,
                    'terminal_id'=>$terminal->id,
                    'order_status_id'=>9,
                    'createdBy'=>Auth()->user()->idnumber,
                ]);
                foreach (Request()->item as $row) {
                    $orders->order_items()->create([
                        'order_item_id'=>$row['itemid'],
                        'order_item_qty'=>$row['returnqty'],
                        'order_item_charge_price'=>$row['itemchargeprice'],
                        'order_item_cash_price'=>$row['itemcashprice'],
                        'order_item_price'=>$row['itemprice'],
                        'order_item_vat_rate'=>$row['order_item_vat_rate'],
                        'order_item_vat_amount'=>$row['order_item_vat_amount'],
                        'order_item_sepcial_discount'=>$row['order_item_sepcial_discount'],
                        'order_item_discount_amount'=>$row['item_discount'],
                        'order_item_total_amount'=>$row['order_item_total_amount'],
                        'order_item_batchno'=>$row['itembatchno']['id'],
                        'isReturned'=>'1',
                        'createdBy'=>Auth()->user()->idnumber,
                    ]);

                    
                 

                }
                if(Request()->return_order_details['refund_amount'] > 0){
                    $order_vatable_sales_amount = 0;
                    $order_vatexempt_sales_amount = 0;
                    $order_zero_rated_sales_amount = 0;
                    $order_total_sales_vat_incl_amount = 0;
                    $order_vat_amount = 0;
                    $order_vat_net_amount = 0;
                    $order_senior_citizen_amount = 0;
                    $order_due_amount = 0;
                    $order_total_mount = 0;
                    foreach (Request()->item as $row) {
                        $customer = vwCustomers::where('id',Request()->return_order_details['customer_id'])->first();
                        $selectorderitems = DB::connection('sqlsrv_pos')->table('orderItems')->where('order_item_id',$row['return_itemid'])->first();
                        $order_total_sales_vat_incl_amount += $selectorderitems->order_item_total_amount;
                        $order_vat_amount += $selectorderitems->order_item_vat_amount;
                        $order_vat_net_amount += $selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount;
                        $order_total_mount += $selectorderitems->order_item_total_amount;
                        if($customer->customer_type !='regular'){
                            $order_vatable_sales_amount += 0;
                            $order_vatexempt_sales_amount += $selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount; 
                            $order_senior_citizen_amount += ($selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount) * 0.20;
                            $order_due_amount += $selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount - (($selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount) * 0.20);
                            
                        }else{
                            $order_vatable_sales_amount += $selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount;
                            $order_due_amount += $selectorderitems->order_item_total_amount - $selectorderitems->order_item_vat_amount;
                        }
                    }
                    
                    $payment = Payments::create([
                        'order_id'=>$orders->id,
                        'sales_invoice_number'=>'',
                        'payment_transaction_number'=>'',
                        'payment_date'=>Carbon::now(),
                        'payment_method_id'=>1,
                        'payment_method_card_id'=>'',
                        'payment_approval_code'=>'',
                        'payment_vatable_sales_amount'=>(float)Request()->order_computation['order_vatable_sales_amount'] - $order_vatable_sales_amount,
                        'payment_vatable_exempt_sales_amount'=>(float)Request()->order_computation['order_vatexempt_sales_amount'] -  $order_vatexempt_sales_amount,
                        'payment_zero_rated_sales_amount'=>(float)Request()->order_computation['order_zero_rated_sales_amount'] - $order_zero_rated_sales_amount,
                        'payment_total_sales_vat_incl_amount'=>(float)Request()->order_computation['order_total_sales_vat_incl_amount'] - $order_total_sales_vat_incl_amount,
                        'payment_vatable_amount'=>(float)Request()->order_computation['order_vat_amount'] - $order_vat_amount,
                        'payment_amount_net_of_vat'=>(float)Request()->order_computation['order_vat_net_amount'] - $order_vat_net_amount,
                        'payment_discount_amount'=>(float)Request()->order_computation['order_senior_citizen_amount'] - $order_senior_citizen_amount,
                        'payment_amount_due'=>(float) Request()->order_computation['order_due_amount'] -  $order_due_amount,
                        'payment_received_amount'=>(float) 0,
                        'payment_changed_amount'=>(float) 0,
                        'payment_total_amount'=>(float) Request()->order_computation['order_total_payment_amount'] - $order_total_mount,
                        'payment_refund_amount'=>(float)0,
                        'terminal_id'=>$terminal->id,
                        'createdBy'=>Auth()->user()->idnumber,
                        'user_id'=>Auth()->user()->idnumber,
                        'shift_id'=>Auth()->user()->shift,
                    ]);
                }

                $returnorderid = $orders->id;
            }
            $return = ReturnTransaction::create([
                'order_id'=>$returnorderid,
                'returned_order_id'=>Request()->return_order_details['order_id'],
                'refund_transaction_number'=>$generat_or_series,
                'refund_date'=>Carbon::now(),
                'refund_method_id'=>'1',
                'refund_amount'=> Request()->return_order_details['refund_amount'] ?? '',
                'refunded_to'=>Request()->return_order_details['customer_id'] ?? '',
                'refund_reason'=>Request()->return_order_details['refund_reason'] ?? '',
                'refund_status_id'=>'1',
                'report_date'=>Carbon::now(),
                'terminal_id'=>$terminal->id,
                'sales_batch_number'=>Request()->return_order_details['sales_batch_number'],
                'sales_batch_transaction_date'=>Request()->return_order_details['sales_batch_transaction_date'],
                'user_id'=>Auth()->user()->idnumber,
                'shift_id'=>Auth()->user()->shift,
                'createdBy'=>Auth()->user()->idnumber,
            ]);

            if(Request()->return_order_details['return_type'] =='exchanged') {
                foreach (Request()->item as $row) {
                    $return->refund_items()->create([
                        'returned_order_item_id' => $row['return_itemid'],
                        'returned_order_item_batchno' => $row['return_itembatchno'],
                        'returned_order_item_qty' => $row['return_itemqty'],
                        'returned_order_item_charge_price' => $row['return_itemchargeprice'],
                        'returned_order_item_cash_price' => $row['return_itemcashprice'],
                        'returned_order_item_price' => $row['return_itemprice'],
                        'returned_order_item_vat_rate' => $row['return_order_item_vat_rate'],
                        'returned_order_item_vat_amount' => $row['return_order_item_vat_amount'],
                        'returned_order_item_sepcial_discount' => $row['return_order_item_sepcial_discount'],
                        'returned_order_item_total_amount' =>$row['return_order_item_total_amount'],
                        'returned_order_item_discount_amount' =>$row['return_order_item_discount_amount'],

                        'order_item_id' => $row['itemid'],
                        'order_item_batchno' => $row['itembatchno']['id'],
                        'order_item_qty' => $row['itemqty'],
                        'order_item_charge_price' => $row['itemchargeprice'],
                        'order_item_cash_price' => $row['itemcashprice'],
                        'order_item_price' => $row['itemprice'],
                        'order_item_vat_rate' => $row['order_item_vat_rate'],
                        'order_item_vat_amount' => $row['order_item_vat_amount'],
                        'order_item_sepcial_discount' => $row['item_discount'],
                        'order_item_total_amount' =>$row['order_item_total_amount'],
                        'order_item_discount_amount' =>$row['order_item_discount_amount'],
                        'createdBy'=>Auth()->user()->idnumber,
                    ]);
                }
            }else{
                foreach (Request()->item as $row) {
                    $return->refund_items()->create([
                        'returned_order_item_id' => $row['itemid'],
                        'returned_order_item_batchno' => $row['order_item_batchno'],
                        'returned_order_item_qty' => $row['return_qty'],
                        'returned_order_item_charge_price' => $row['itemchargeprice'] * $row['return_qty'],
                        'returned_order_item_cash_price' => $row['itemcashprice'],
                        'returned_order_item_price' => $row['itemprice'],
                        'returned_order_item_vat_rate' => $row['order_item_vat_rate'],
                        'returned_order_item_vat_amount' => ($row['order_item_vat_amount'] / $row['itemqty'])  * $row['return_qty'],
                        'returned_order_item_sepcial_discount' => ($row['order_item_sepcial_discount'] / $row['itemqty'])  * $row['return_qty'],
                        'returned_order_item_total_amount' => ($row['order_item_total_amount'] / $row['itemqty'])  * $row['return_qty'],
                        'returned_order_item_discount_amount' =>($row['order_item_discount_amount'] / $row['itemqty']) * $row['return_qty'],

                        'order_item_id' => $row['itemid'],
                        'order_item_batchno' => $row['order_item_batchno'],
                        'order_item_qty' => $row['return_qty'],
                        'order_item_charge_price' => $row['itemchargeprice'] * $row['return_qty'],
                        'order_item_cash_price' => $row['itemcashprice'],
                        'order_item_price' => $row['itemprice'],
                        'order_item_vat_rate' => $row['order_item_vat_rate'],
                        'order_item_vat_amount' => ($row['order_item_vat_amount'] / $row['itemqty'])  * $row['return_qty'],
                        'order_item_sepcial_discount' => ($row['order_item_sepcial_discount'] / $row['itemqty'])  * $row['return_qty'],
                        'order_item_total_amount' => ($row['order_item_total_amount'] / $row['itemqty'])  * $row['return_qty'],
                        'order_item_discount_amount' =>($row['order_item_discount_amount'] / $row['itemqty']) * $row['return_qty'],
                        'createdBy'=>Auth()->user()->idnumber,
                    ]);
                }
            }
            $tran_sequenceno->update([
                'seq_no'=>(int)$tran_sequenceno->seq_no + 1,
                'recent_generated'=>$generate_trans_series,
            ]);
            $sequenceno->update([
                'seq_no'=>(int)$sequenceno->seq_no + 1,
                'recent_generated'=>$generatesequence,
            ]);

            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
        
    }

    public function submitexcesspayment(Request $request){


       
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $return = ReturnTransaction::where('id',Request()->return_payment_details['refund_id'])->first();
            if($return){
                $payment = Payments::where('order_id',Request()->return_payment_details['order_id'])->first();

                $terminal = (new Terminal)->terminal_details();
                $or_sequenceno = (new SeriesNo())->get_sequence('PSI',$terminal->terminal_code);
                $tran_sequenceno =(new SeriesNo())->get_sequence('PTN',$terminal->terminal_code);
                
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
                $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
                if($or_sequenceno->isSystem == '0'){
                    $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
                }
    
                $payment->update([
                    'sales_invoice_number' => $generat_or_series,
                    'payment_transaction_number' => $generate_trans_series,
                    'payment_received_amount' => (float)Request()->return_payment['amounttendered']  ?? '0',
                    'payment_changed_amount' => (float)Request()->return_payment['change'] ?? '0',
                    'payment_method_id' => (float)Request()->return_payment['paymenttype'] ?? '0',
                    'createdBy' => Auth()->user()->idnumber,
                    'user_id' => Auth()->user()->idnumber,
                    'shift_id' => Auth()->user()->shift,
                ]);

                // $payment->update([
                //     'sales_invoice_number'=>$generat_or_series,
                //     'payment_transaction_number'=>$generate_trans_series,
                //     'payment_received_amount'=>(float)Request()->return_payment_details['cashtendered']  ?? '0',
                //     'payment_changed_amount'=>(float)Request()->return_payment_details['changed'] ?? '0',
                //     'createdBy'=>Auth()->user()->idnumber,
                //     'user_id'=>Auth()->user()->idnumber,
                //     'shift_id'=>Auth()->user()->shift,
                // ]);
                $transaction = FmsTransactionCode::where('code', 'RMS')->where('isActive', 1)->first();

                $return_items = ReturnDetailsTransaction::where('refund_id',Request()->return_payment_details['refund_id'])->get();
                foreach($return_items as $row){
                    // return order 
                    $return_warehouseitem = DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id', (int)$row['returned_order_item_id'])->first();
                    $return_batch = DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['returned_order_item_batchno'])->where('item_Id', (int)$row['returned_order_item_id'])->first();
                    if($return_batch) {
                        $isConsumed = '0';
                        $usedqty = $return_batch->item_Qty_Used - $row['returned_order_item_qty'];
                        if($usedqty >= $return_batch->item_Qty) {
                            $isConsumed = '1';
                        }
                        DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['returned_order_item_batchno'])->update([
                            'item_Qty_Used'=>  (int)$return_batch->item_Qty_Used - (int)$row['returned_order_item_qty'],
                            'isConsumed'=>  $isConsumed 
                        ]);

                        
                        InventoryTransaction::create([
                            'branch_Id' => $return_warehouseitem->branch_id,
                            'warehouse_Group_Id' => 2,
                            'warehouse_Id' => $return_warehouseitem->warehouse_Id,
                            'transaction_Item_Id' =>  $row['returned_order_item_id'],
                            'transaction_Date' => Carbon::now(),
                            'transaction_ORNumber' => $generat_or_series,
                            'trasanction_Reference_Number' => $generate_trans_series,
                            'transaction_Item_UnitofMeasurement_Id' => $return_batch->item_UnitofMeasurement_Id,
                            'transaction_Qty' => $row['returned_order_item_qty'],
                            'transaction_Item_OnHand' => $return_warehouseitem->item_OnHand + $row['returned_order_item_qty'],
                            'transaction_Item_ListCost' => $row['returned_order_item_total_amount'],
                            'transaction_UserID' =>  Auth()->user()->idnumber,
                            'createdBy' => Auth()->user()->idnumber,
                            'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                        ]);

                    }
                    DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id',(int)$row['returned_order_item_id'])->update([
                        'item_OnHand'=> (int)$return_warehouseitem->item_OnHand + (int)$row['returned_order_item_qty']
                    ]);


                    // new order 
                    $order_warehouseitem = DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id', (int)$row['order_item_id'])->first();
                    $order_batch = DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['order_item_batchno'])->where('item_Id', (int)$row['order_item_id'])->first();
                    if($order_batch) {
                        $isConsumed = '0';
                        $usedqty = $order_batch->item_Qty_Used + $row['order_item_qty'];
                        if($usedqty >= $order_batch->item_Qty) {
                            $isConsumed = '1';
                        }
                        DB::connection('sqlsrv_mmis')->table('itemBatchNumberMaster')->where('id', (int)$row['order_item_batchno'])->update([
                            'item_Qty_Used'=>  (int)$order_batch->item_Qty_Used + (int)$row['order_item_qty'],
                            'isConsumed'=>  $isConsumed 
                        ]);

                        
                        InventoryTransaction::create([
                           'branch_Id' => $order_warehouseitem->branch_id,
                           'warehouse_Group_Id' => 2,
                           'warehouse_Id' => $order_warehouseitem->warehouse_Id,
                           'transaction_Item_Id' =>  $row['order_item_id'],
                           'transaction_Date' => Carbon::now(),
                           'transaction_ORNumber' => $generat_or_series,
                           'trasanction_Reference_Number' => $generate_trans_series,
                           'transaction_Item_UnitofMeasurement_Id' => $return_batch->item_UnitofMeasurement_Id,
                           'transaction_Qty' => $row['order_item_qty'],
                           'transaction_Item_OnHand' => $order_warehouseitem->item_OnHand - $row['order_item_qty'],
                           'transaction_Item_ListCost' => $row['order_item_total_amount'],
                           'transaction_UserID' =>  Auth()->user()->idnumber,
                           'createdBy' => Auth()->user()->idnumber,
                           'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                       ]);

                    }
                    DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id',(int)$row['order_item_id'])->update([
                        'item_OnHand'=> (int)$order_warehouseitem->item_OnHand - (int)$row['order_item_qty']
                    ]);
                }
                $return->update([
                    'refund_status_id'=>'11',
                ]);
            }
            if($or_sequenceno->isSystem == '0'){
                $or_sequenceno->update([
                  'manual_seq_no'=>(int)$or_sequenceno->manual_seq_no + 1,
                  'manual_recent_generated'=>$generat_or_series,
                ]);
            }else{
                $or_sequenceno->update([
                  'seq_no'=>(int)$or_sequenceno->seq_no + 1,
                  'recent_generated'=>$generat_or_series,
                ]);
            }
            $trans_seriesno = $tran_sequenceno->seq_no + 1;
            $tran_sequenceno->update([
                'seq_no'=>$trans_seriesno,
                'recent_generated'=>$generate_trans_series,
            ]);
          
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $terminal = (new Terminal)->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('RTN',$terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('RTN',$terminal->terminal_code);
            
            
            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0'){
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            // order transaction 
            $sequenceno = (new SeriesNo())->get_sequence('PPLN',$terminal->terminal_code);
            $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
            if($sequenceno->isSystem == '0'){
                $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
            }
            $orderid = Request()->orderdetials['orderid'] ?? '';
            $returnorderid = Request()->orderdetials['orderid'] ?? '';
            $refunddableamount = Request()->returnexchangeitemcomputation['totalamount'];
            if(Request()->refundpayload['refundtype']['id'] == "3") {
                $orders = Orders::create([
                    'branch_id'=> Auth()->user()->branch_id,
                    'warehouse_id'=> Auth()->user()->warehouse_id,
                    'customer_id'=>Request()->customerdetails ?? '',
                    'pick_list_number'=> $generatesequence,
                    'order_date'=> Carbon::now(),
                    'order_total_line_item_ordered'=> count(Request()->returnitem),
                    'order_vatable_sales_amount'=> Request()->returnexchangeitemcomputation['vatsales'],
                    'order_vatexempt_sales_amount'=> Request()->returnexchangeitemcomputation['vatexemptsale'],
                    'order_zero_rated_sales_amount'=> Request()->returnexchangeitemcomputation['zeroratedsale'],
                    'order_total_sales_vat_incl_amount'=> Request()->returnexchangeitemcomputation['totalsalesvatinclude'],
                    'order_vat_amount'=> Request()->returnexchangeitemcomputation['vatamount'],
                    'order_vat_net_amount'=> Request()->returnexchangeitemcomputation['amountnetvat'],
                    'order_senior_citizen_amount'=> Request()->returnexchangeitemcomputation['lessdiscount'],
                    'order_due_amount'=> Request()->returnexchangeitemcomputation['amountdue'],
                    'order_total_payment_amount'=> Request()->returnexchangeitemcomputation['totalamount'],
                    'pa_userid'=>Auth()->user()->idnumber,
                    'cashier_user_id'=>0,
                    'checker_userid'=>0,
                    'terminal_id'=>$terminal->id,
                    'order_status_id'=>9,
                    'createdBy'=>Auth()->user()->idnumber,
                ]);

                foreach (Request()->returnitem as $row) {
                    $orders->order_items()->create([
                        'order_item_id'=>$row['id'],
                        'order_item_qty'=>$row['itemqty'],
                        'order_item_charge_price'=>$row['itemprice'],
                        'order_item_cash_price'=>$row['itemoldprice'],
                        'order_item_price'=>$row['itemprice'],
                        'order_item_vat_rate'=>$row['vatablerate'],
                        'order_item_vat_amount'=>$row['itemisvatable'],
                        'order_item_sepcial_discount'=>$row['Discountrate'],
                        'order_item_discount_amount'=>$row['itemisallowdiscount'],
                        'order_item_total_amount'=>$row['itemdiscountedamount'],
                        'order_item_batchno'=>$row['itembatchno']['id'],
                        'isReturned'=>'1',
                        'createdBy'=>Auth()->user()->idnumber,
                    ]);

                }
                $orderid = $orders->id; 
                $refunddableamount = (Request()->refundamountdetails['refundamount'] - Request()->returnexchangeitemcomputation['totalamount']);
            }
            $returnorder_details = Payments::where('order_id',$returnorderid)->first();
            $return = ReturnTransaction::create([
                'order_id'=>$orderid,
                'returned_order_id'=>$returnorderid,
                'refund_transaction_number'=>$generat_or_series,
                'refund_date'=>Carbon::now(),
                'refund_method_id'=>Request()->refundpayload['refundtype']['id'] ?? '',
                'refund_amount'=> $refunddableamount,
                'refunded_to'=>Request()->customerdetails ?? '',
                'refund_reason'=>Request()->refundpayload['remarks'] ?? '',
                'refund_status_id'=>'1',
                'report_date'=>Carbon::now(),
                'terminal_id'=>$terminal->id,
                'sales_batch_number'=>$returnorder_details->sales_batch_number,
                'sales_batch_transaction_date'=>$returnorder_details->sales_batch_transaction_date,
                'user_id'=>Auth()->user()->idnumber,
                'shift_id'=>Auth()->user()->shift,
                'createdBy'=>Auth()->user()->idnumber,
            ]);
                
            foreach (Request()->returnitem as $row) {
                $return->refund_items()->create([
                    'returned_order_item_id' => $row['exchange_item_id'],
                    'returned_order_item_batchno' => $row['exchange_order_batchno']['id'],
                    'returned_order_item_qty' => $row['itemqty_return'],
                    'returned_order_item_charge_price' => $row['exchange_item_charge_price'],
                    'returned_order_item_cash_price' => $row['exchange_item_cash_price'],
                    'returned_order_item_price' => $row['exchange_item_price'],
                    'returned_order_item_vat_rate' => $row['exchange_item_vat_rate'],
                    'returned_order_item_vat_amount' => $row['exchange_item_vat_amount'],
                    'returned_order_item_sepcial_discount' => $row['exchange_item_sepcial_discount'],
                    'returned_order_item_total_amount' => $row['exchange_item_item_total_amount'],
                    'returned_order_item_discount_amount' => $row['exchange_item_item_discount_amount'],

                    'order_item_id' => $row['id'],
                    'order_item_batchno' => $row['itembatchno']['id'],
                    'order_item_qty' => $row['itemqty'],
                    'order_item_charge_price' => $row['itemoldprice'],
                    'order_item_cash_price' => $row['itemprice'],
                    'order_item_price' => $row['itemprice'],
                    'order_item_vat_rate' => $row['vatablerate'],
                    'order_item_vat_amount' => $row['itemisvatable'],
                    'order_item_sepcial_discount' => $row['itemdiscount'],
                    'order_item_total_amount' => $row['itemtotalamount'],
                    'order_item_discount_amount' => $row['itemdiscountedamount'],
                    'createdBy'=>Auth()->user()->idnumber,
                ]);
            }

            $tran_sequenceno->update([
                'seq_no'=>(int)$tran_sequenceno->seq_no + 1,
                'recent_generated'=>$generate_trans_series,
            ]);

            $sequenceno->update([
                'seq_no'=>(int)$sequenceno->seq_no + 1,
                'recent_generated'=>$generatesequence,
            ]);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $return = ReturnTransaction::find($id);
            $status = $request->refundstatus;
            $totalamountexcess = (float)$request->refunddetails['totalamountexcess'];
            $refundstatus = '';
            if(Request()->refund_method_id == '3' && $status == '6'){
                $refundstatus = '6';
            }else if((Request()->refund_method_id == '1' || Request()->refund_method_id == '2') && $status == '6'){

                $refundstatus  = '9';
                $returnitem = ReturnDetailsTransaction::where('refund_id',$id)->first();
                $batch = ItemBatch::where('id',(int)$returnitem->returned_order_item_batchno)->first();
                $warehouseitem = Warehouseitems::where('id',(int)$returnitem->returned_order_item_id)->where('branch_id',(int)Auth()->user()->branch_id)->where('warehouse_id',(int)Auth()->user()->warehouse_id)->first();

                if($batch){
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $returnitem->returned_order_item_qty;
                    if($usedqty >= $batch->item_Qty){
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used += (int)$returnitem->returned_order_item_qty;
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem){
                    $warehouseitem->item_OnHand += (int)$returnitem->returned_order_item_qty;
                    $warehouseitem->save();
                }
                
            }
            if($totalamountexcess == 0){
                $refundstatus  = '9';
            }
            if($status == 4){
                $refundstatus  = '4';
            }
           
            
            $return->update([
                'refund_status_id'=>$refundstatus,
                'approvedby'=>Auth()->user()->idnumber,
                'approved_at'=>Carbon::now()
            ]);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
            
        } catch (Throwable $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
