<?php

namespace App\Http\Controllers\POS;

use Throwable;
use Carbon\Carbon;
use App\Models\POS\Orders;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Support\Facades\DB;
use App\Models\POS\vwReturnDetails;
use App\Http\Controllers\Controller;
use App\Models\POS\ReturnTransaction;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Helpers\PosSearchFilter\ReturnList;
use App\Models\POS\Customers;
use App\Models\POS\ReturnDetailsTransaction;

class ReturnTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = (new ReturnList())->searchable();
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
                    'pa_userid'=>Auth()->user()->id,
                    'cashier_user_id'=>0,
                    'checker_userid'=>0,
                    'terminal_id'=>$terminal->id,
                    'order_status_id'=>7,
                    'createdBy'=>Auth()->user()->id,
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
                        'createdBy'=>Auth()->user()->id,
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
                'user_id'=>Auth()->user()->id,
                'shift_id'=>Auth()->user()->shift,
                'createdBy'=>Auth()->user()->id,
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
                    'createdBy'=>Auth()->user()->id,
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



    public function store2(Request $request){
    }

    public function submitexcesspayment(Request $request){
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $possetting = POSSettings::with('bir_settings')->where('isActive','1')->first();
            $terminal = (new Terminal)->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI',$terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('PTN',$terminal->terminal_code);
            
            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0'){
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            $paymenttype = Request()->payload['checkoutpayload']['paymenttype'];
            $totalamount = Request()->payload['checkoutpayload']['amounttendered'];
            $exchange_totalamount = Request()->payload['exchange_totalamount'];
            $totalamountexcess = Request()->payload['totalamountexcess'];
            $cardtype = '';
            if($paymenttype !="1"){
                $totalamount = Request()->payload['checkoutpayload']['totalamount'];
                $cardtype = Request()->payload['checkoutpayload']['cardtype'];
            }
            $orderid = Request()->payload['checkoutpayload']['orderid'] ?? '';
            $orders = Orders::where('id', $orderid)->first();
            $customers = Customers::where('id', $orders->customer_id)->first();
            $isdiscounted = 0;
            if($customers){
                if($customers->isSeniorCitizen == '1'){
                    $isdiscounted = '1';
                }
                if($customers->isPWD == '1'){
                    $isdiscounted = '1';
                }
            }
            // $netvat = 0;
            // $lessvat =0;
            // $lessdiscount = 0;
            // $amountdue = 0;
            // $addvat = 0;
            // $totalamountdue = 0;
            // $vatsales = 0;
            // $vatexempt = 0;
            // $zerorated = 0;
            // $vatamount = 0;
            // $totalsalevatinclude = 0;
            // if($isdiscounted == '1'){
            //     $vatamount = (($totalamountexcess / ($possetting->vat_rate * 100)) * $possetting->vat_rate);
            //     $totalsalevatinclude = $totalamountexcess;
            //     $lessvat = $vatamount;
            //     $netvat = $totalamountexcess - $vatamount;
            //     $lessdiscount = ($netvat * $possetting->seniorcitizen_discount_rate);
            //     $vatexempt = 0;
            //     $amountdue = ($totalamountexcess - $lessvat - $lessdiscount);
            //     $addvat = 0;
            //     $totalamountdue =  $amountdue + $addvat;
            // }else{
               
            // }
            $zerorated = 0;
            $vatamount = Request()->payload['refund_footer']['vatsales'];
            $totalsalevatinclude =Request()->payload['refund_footer']['totalsalesvatinclude'];
            $lessvat = Request()->payload['refund_footer']['lessvat'];
            $netvat =  Request()->payload['refund_footer']['amountnetvat'];
            $lessdiscount =Request()->payload['refund_footer']['lessdiscount'];
            $vatexempt = Request()->payload['refund_footer']['vatexemptsale'];
            $amountdue = Request()->payload['refund_footer']['amountdue'];
            $addvat = Request()->payload['refund_footer']['vatamount'];
            $totalamountdue =  Request()->payload['refund_footer']['totalamount'];
            // totalamountexcess

            $payment = Payments::create([
                'order_id'=>$orderid,
                'sales_invoice_number'=>$generat_or_series,
                'payment_transaction_number'=>$generate_trans_series,
                'payment_date'=>Carbon::now(),
                'payment_method_id'=> $paymenttype,
                'payment_method_card_id'=>$cardtype,
                'payment_approval_code'=>Request()->payload['checkoutpayload']['approvalcode'] ?? '',
                'payment_vatable_sales_amount'=>(float)$netvat,
                'payment_vatable_exempt_sales_amount'=>(float)$vatexempt,
                'payment_zero_rated_sales_amount'=>(float)$zerorated,
                'payment_total_sales_vat_incl_amount'=>(float)$totalsalevatinclude,
                'payment_vatable_amount'=>(float)$vatamount,
                'payment_amount_net_of_vat'=>(float)$netvat,
                'payment_discount_amount'=>(float)$lessdiscount,
                'payment_amount_due'=>(float)$amountdue,
                'payment_received_amount'=>(float)$totalamount ?? '0',
                'payment_changed_amount'=>(float)Request()->payload['checkoutpayload']['change'] ?? '',
                'payment_total_amount'=>(float)($totalamountdue),
                'payment_refund_amount'=>(float)Request()->payload['return_totalamount'],
                'terminal_id'=>Request()->payload['checkoutpayload']['terminalid'] ?? '1',
                'createdBy'=>Auth()->user()->id,
                'user_id'=>Auth()->user()->id,
                'shift_id'=>Auth()->user()->shift,
              ]);
            
            //   exchange item 
            foreach (Request()->payload['list_of_exchangeitem'] as $row) {
                $batch = ItemBatch::where('id',(int)$row['item_batchno'])->first();
                $warehouseitem = Warehouseitems::where('id',(int)$row['item_id'])->where('branch_id',(int)Auth()->user()->branch_id)->where('warehouse_id',(int)Auth()->user()->warehouse_id)->first();
                if($batch){
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $row['qty'];
                    if($usedqty >= $batch->item_Qty){
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used += (int)$row['qty'];
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem){
                    $warehouseitem->item_OnHand -= (int)$row['qty'];
                    $warehouseitem->save();
                }
            }
            //   return item 
            foreach (Request()->payload['list_of_returnitem'] as $row) {
                $batch = ItemBatch::where('id',(int)$row['item_batchno'])->first();
                $warehouseitem = Warehouseitems::where('id',(int)$row['item_id'])->where('branch_id',(int)Auth()->user()->branch_id)->where('warehouse_id',(int)Auth()->user()->warehouse_id)->first();
                if($batch){
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used - $row['qty'];
                    if($usedqty >= $batch->item_Qty){
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used -= (int)$row['qty'];
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem){
                    $warehouseitem->item_OnHand += (int)$row['qty'];
                    $warehouseitem->save();
                }
            }

            $return = ReturnTransaction::where('id',Request()->payload['refundid']);
            $return->update([
                'refund_status_id'=>'9',
                'approvedby'=>Auth()->user()->id,
                'approved_at'=>Carbon::now()
            ]);

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
            
            if(Request()->refund_method_id == '3' && $status == '6'){
                $status = '6';
            }else if((Request()->refund_method_id == '1' || Request()->refund_method_id == '2') && $status == '6'){

                $status = '9';
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
            if($status == '4'){
                $status = '4';
            }
            if($totalamountexcess == 0){
                $status = '9';
            }
            
            $return->update([
                'refund_status_id'=>$status,
                'approvedby'=>Auth()->user()->id,
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
