<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\POS\Orders;
use App\Models\POS\Payments;
use Illuminate\Http\Request;

use App\Models\POS\POSSettings;
use App\Models\POS\vwRefundReceipt;
use App\Models\POS\vwReturnDetails;
use App\Http\Controllers\Controller;
use App\Models\POS\vwCustomerOrders;
use App\Models\POS\vwPaymentReceipt;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\vwPaymentReceiptItems;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;

class PaymentTransaction extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    
    protected $orders_data = [];
    protected $item_details = [];
    protected $json_file ='';

    public function index()
    {
        //
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

   
    public function save_payment(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('PTN', $terminal->terminal_code);

            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            $orderdetails = vwCustomerOrders::where('order_id',Request()->payment_details['order_id'])->where('customer_id',Request()->payment_details['customer_id'])->get();
            $paymenttype = Request()->payment_details['paymenttype'];
            $totalamount = Request()->payment_details['amounttendered'];
            $cardtype = '';
            if($paymenttype !="1") {
                $cardtype = Request()->payment_details['cardtype'];
            };

            $payment = Payments::create([
              'order_id'=>$orderdetails[0]->order_id,
              'sales_invoice_number'=>$generat_or_series,
              'payment_transaction_number'=>$generate_trans_series,
              'payment_date'=>Carbon::now(),
              'payment_method_id'=>Request()->payment_details['paymenttype'] ?? '',
              'payment_method_card_id'=>$cardtype,
              'payment_approval_code'=>Request()->payment_details['approvalcode'] ?? '',
              'payment_vatable_sales_amount'=>(float)$orderdetails[0]['order_vatable_sales_amount'] ?? '',
              'payment_vatable_exempt_sales_amount'=>(float)$orderdetails[0]['order_vatexempt_sales_amount'] ?? '',
              'payment_zero_rated_sales_amount'=>(float)$orderdetails[0]['order_zero_rated_sales_amount'] ?? '',
              'payment_total_sales_vat_incl_amount'=>(float)$orderdetails[0]['order_total_sales_vat_incl_amount'] ?? '',
              'payment_vatable_amount'=>(float)$orderdetails[0]['order_vat_amount'] ?? '',
              'payment_amount_net_of_vat'=>(float)$orderdetails[0]['order_vat_net_amount'] ?? '',
              'payment_discount_amount'=>(float)$orderdetails[0]['order_senior_citizen_amount'] ?? '',
              'payment_amount_due'=>(float)$orderdetails[0]['order_due_amount'] ?? '',
              'payment_received_amount'=>(float)Request()->payment_details['amounttendered'] ?? '',
              'payment_changed_amount'=>(float)Request()->payment_details['change'] ?? '',
              'payment_total_amount'=>(float)$orderdetails[0]['order_total_payment_amount'] ?? '',
              'payment_refund_amount'=> '0',
              'terminal_id'=>$terminal->id,
              'user_id'=>Auth()->user()->idnumber,
              'shift_id'=>Auth()->user()->shift,
              'createdBy'=>Auth()->user()->idnumber,
            ]);

            $orders = Orders::where('id', $orderdetails[0]->order_id)->first();
            $orders->update([
                'order_status_id'=>'9'
            ]);
            
            $transaction = FmsTransactionCode::where('code', 'PY')->where('isActive', 1)->first();
            foreach ($orderdetails as $row) {
                // $batch = ItemBatch::where('id', (int)$row['order_item_batchno'])->where('item_Id', (int)$row['order_item_id'])->first();
                $warehouseitem = DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('warehouse_Id',$row['warehouse_Id'])->where('item_Id', (int)$row['order_item_id'])->first();
                $batch = DB::connection('sqlsrv_mmis')->table('itemBatchModelNumberMaster')->where('id', (int)$row['order_item_batchno'])->where('item_Id', (int)$row['order_item_id'])->first();
                if($batch) {
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $row['order_item_qty'];
                    if($usedqty >= $batch->item_Qty) {
                        $isConsumed = '1';
                    }
                    DB::connection('sqlsrv_mmis')->table('itemBatchModelNumberMaster')->where('id', (int)$row['order_item_batchno'])->update([
                        'item_Qty_Used'=>  (int)$batch->item_Qty_Used + (int)$row['order_item_qty'],
                        'isConsumed'=>  $isConsumed 
                    ]);
                                        
                    InventoryTransaction::create([
                        'branch_Id' => $warehouseitem->branch_id,
                        'warehouse_Group_Id' => $row['item_InventoryGroup_Id'],
                        'warehouse_Id' => $warehouseitem->warehouse_Id,
                        'transaction_Item_Id' =>  $row['order_item_id'],
                        'transaction_Date' => Carbon::now(),
                        'trasanction_Reference_Number' =>$generate_trans_series,
                        'transaction_ORNumber' => $generat_or_series,
                        'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                        'transaction_Qty' => $row['order_item_qty'],
                        'transaction_Item_OnHand' => $warehouseitem->item_OnHand - $row['order_item_qty'],
                        'transaction_Item_ListCost' => $row['order_item_total_amount'],
                        'transaction_UserID' =>  Auth()->user()->idnumber,
                        'createdBy' => Auth()->user()->idnumber,
                        'transaction_Acctg_TransType' =>  $transaction->code ?? '',
                    ]);

                }
                DB::connection('sqlsrv_mmis')->table('warehouseitems')->where('item_Id',(int)$row['order_item_id'])->update([
                    'item_OnHand'=> (int)$warehouseitem->item_OnHand - (int)$row['order_item_qty']
                ]);
                
            }
            if($or_sequenceno->isSystem == '0') {
                $or_sequenceno->update([
                  'manual_seq_no'=>(int)$or_sequenceno->manual_seq_no + 1,
                  'manual_recent_generated'=>$generat_or_series,
                ]);
            } else {
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


            

            $transaction['ornumber'] = $generat_or_series;
            $transaction['transid'] = $payment->id;
            $transaction['transno'] = $payment->payment_transaction_number;
            // $or_receipt = $this->orprintout(Request()->payload,$transaction);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
            $pos_printout_layout = 0;
            if($series_setting->isSystem == 1 && $series_setting->isPos == 0) {
                $pos_printout_layout = 1;
            } elseif($series_setting->isSystem == 0 && $series_setting->isPos == 1) {
                $pos_printout_layout = 2;
            }
            $or_receipt = $this->orprintout($orderdetails[0]->order_id);
            return response()->json(["message" =>  'Record successfully saved','status'=>'200','or_receipt'=>$or_receipt,'seriesno'=>$generat_or_series,'pos_printout_layout'=>$pos_printout_layout], 200);
        } catch (\Exception $e) {

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
            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('PTN', $terminal->terminal_code);

            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            $paymenttype = Request()->payload['paymenttype'];
            $totalamount = Request()->payload['amounttendered'];
            $discounttype = Request()->payload['customer_payload']['discounttype'];
            $cardtype = '';
            if($paymenttype !="1") {
                $totalamount = Request()->payload['totalamount'];
                $cardtype = Request()->payload['cardtype'];
            }
            $totalamountdue =Request()->payload['cart_footer']['amountdue'];
            $lessdiscount =(float)Request()->payload['cart_footer']['lessdiscount'];
            if($discounttype == 'regular') {
                if($lessdiscount > 0) {
                    $totalamountdue = (Request()->payload['cart_footer']['amountdue'] + Request()->payload['cart_footer']['vatamount']) - $lessdiscount;
                } else {
                    $totalamountdue = (Request()->payload['cart_footer']['amountdue'] + Request()->payload['cart_footer']['vatamount']);
                }

            }
            $payment = Payments::create([
              'order_id'=>Request()->payload['orderid'] ?? '',
              'sales_invoice_number'=>$generat_or_series,
              'payment_transaction_number'=>$generate_trans_series,
              'payment_date'=>Carbon::now(),
              'payment_method_id'=>Request()->payload['paymenttype'] ?? '',
              'payment_method_card_id'=>$cardtype,
              'payment_approval_code'=>Request()->payload['approvalcode'] ?? '',
              'payment_vatable_sales_amount'=>(float)Request()->payload['cart_footer']['vatsales'] ?? '',
              'payment_vatable_exempt_sales_amount'=>(float)Request()->payload['cart_footer']['vatexemptsale'] ?? '',
              'payment_zero_rated_sales_amount'=>(float)Request()->payload['cart_footer']['zeroratedsale'] ?? '',
              'payment_total_sales_vat_incl_amount'=>(float)Request()->payload['cart_footer']['totalsalesvatinclude'] ?? '',
              'payment_vatable_amount'=>(float)Request()->payload['cart_footer']['vatamount'] ?? '',
              'payment_amount_net_of_vat'=>(float)Request()->payload['cart_footer']['amountnetvat'] ?? '',
              'payment_discount_amount'=>(float)$lessdiscount ?? '',
              'payment_amount_due'=>(float)Request()->payload['cart_footer']['amountdue'] ?? '',
              'payment_received_amount'=>(float)$totalamount ?? '0',
              'payment_changed_amount'=>(float)Request()->payload['change'] ?? '',
              'payment_total_amount'=>(float)$totalamountdue,
              'payment_refund_amount'=> '0',
              'terminal_id'=>Auth()->user()->terminal_id,
              'user_id'=>Auth()->user()->idnumber,
              'shift_id'=>Auth()->user()->shift,
              'createdBy'=>Auth()->user()->idnumber,
            ]);

            $orders = Orders::where('id', Request()->payload['orderid']);
            $orders->update([
                'order_status_id'=>'9'
            ]);

            foreach (Request()->payload['orderitems'] as $row) {
                $batch = ItemBatch::where('id', (int)$row['itembatchno']['id'])->first();
                $warehouseitem = Warehouseitems::where('id', (int)$row['id']) ->first();
                if($batch) {
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $row['itemqty'];
                    if($usedqty >= $batch->item_Qty) {
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used += (int)$row['itemqty'];
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem) {
                    $warehouseitem->item_OnHand -= (int)$row['itemqty'];
                    $warehouseitem->save();
                }
            }

            if($or_sequenceno->isSystem == '0') {
                $or_sequenceno->update([
                  'manual_seq_no'=>(int)$or_sequenceno->manual_seq_no + 1,
                  'manual_recent_generated'=>$generat_or_series,
                ]);
            } else {
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
            $transaction['ornumber'] = $generat_or_series;
            $transaction['transid'] = $payment->id;
            $transaction['transno'] = $payment->payment_transaction_number;
            // $or_receipt = $this->orprintout(Request()->payload,$transaction);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
            $pos_printout_layout = 0;
            if($series_setting->isSystem == 1 && $series_setting->isPos == 0) {
                $pos_printout_layout = 1;
            } elseif($series_setting->isSystem == 0 && $series_setting->isPos == 1) {
                $pos_printout_layout = 2;
            }
            $or_receipt = $this->orprintout(Request()->payload['orderid']);

            return response()->json(["message" =>  'Record successfully saved','status'=>'200','or_receipt'=>$or_receipt,'pos_printout_layout'=>$pos_printout_layout], 200);
        } catch (\Exception $e) {

            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);

        }
    }

    public function orprintout($order_id)
    {
        $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
        if($series_setting->isSystem == 1 && $series_setting->isPos == 0) {
            return $this->system_generated_orprintout($order_id);
        } elseif($series_setting->isSystem == 0 && $series_setting->isPos == 1) {
            return  $this->manual_generated_orprintout($order_id);
        }
    }

    public function system_generated_orprintout($order_id)
    {
        $receiptdetails = vwPaymentReceipt::where('order_id', $order_id)->first();
        $receiptitems = vwPaymentReceiptItems::where('order_id', $order_id)->get();
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $customertype = 'Regular';
        $addvat = $receiptdetails ? $receiptdetails->payment_vatable_amount : '0';
        $lessvat = '';
        $istype = 'SC/PWD';
        if($receiptdetails->isSeniorCitizen == '1' && $receiptdetails->isPWD == '0') {
            $customertype = 'Senior Discount';
            $addvat = 0.00;
            $lessvat = '-';
        } elseif($receiptdetails->isSeniorCitizen == '0' && $receiptdetails->isPWD == '1') {
            $customertype = 'PWD Discount';
            $addvat = 0.00;
            $lessvat = '-';
        }
        $html = '
        <div class="page">
            <div class="printout-company-details">
                <div class="printout-company-name">'.$possetting->company_name.'</div>
                <div class="printout-company-address">'.$possetting->company_address_bldg.' '.$possetting->company_address_streetno.'</div>
            </div>
            <div class="printout-company-bir-details">
                <div class="printout-company-tin">Vat Reg TIN: '.$possetting->company_tin.'</div>
                <div class="printout-company-min">MIN : '.$receiptdetails->terminal_Machine_Identification_Number.'</div>
                <div class="printout-seriesno">SN : '.$receiptdetails->terminal_serial_number.'/ TN :'.$receiptdetails->payment_id.'</div>
                <div class="printout-seriesno"> TR# '.$receiptdetails->payment_transaction_number.'</div>
            </div>
            <table  >
                <thead  class="header">
                    <tr>
                        <td class="si-td" colspan="3">SALES INVOICE NO.</td>
                        <td class="si-td" >:'.$receiptdetails->sales_invoice_number.'</td>
                    </tr>
                    <tr>
                        <td class="print-cashier" >Cashier</td>
                        <td class="print-cashier-name">: '.$receiptdetails->cashier_name.'</td>
                        <td class="print-date">Date</td>
                        <td class="print-date-value">: '.Carbon::parse($receiptdetails->payment_date)->format('m/d/Y').'</td>
                    </tr>
                    <tr>
                        <td class="print-pa">P.A</td>
                        <td class="print-pa-name">: '.$receiptdetails->pa_name.'</td>
                        <td class="print-time">Time</td>
                        <td class="print-time-value">: '.Carbon::parse($receiptdetails->payment_date)->format('H:i:s').'</td>
                    </tr>
                    
                </thead>
            </table>

            <div class="print-item">
                <table class="print-item-table">
                    <tbody>
                        <tr class="thead">
                            <th class="product">PRODUCT</th>
                            <th class="productqty">QTY</th>
                            <th class="productprice">PRICE</th>
                            <th class="totalamount">AMOUNT</th>
                        </tr>
                        ';
                            // <div>'.$item['brand'].'</div>
                            $counter = 0;
                            $totalamountdue = 0;
                            $totaldiscount = 0;

                        
                            foreach($receiptitems as $item) {
                                $counter++;
                                
                                if($customertype != 'Regular') {
                                   $price = $item['itemchargeprice'];
                                }else{
                                    $price = $item['itemprice'];
                                }

                                $totalamountdue += ($item['itemprice'] * $item['itemqty']);
                                    if((float)$addvat == 0){
                                    
                                    }else{
                                        $totaldiscount += ($item['itemprice'] - $item['itemcashprice']);
                                    }
                                    $html .='
                                    <tr>
                                        <td >
                                            <div>'.$item['itemname'].' '.$item['itemdescription'].'</div>
                                            ('.$item['order_item_batchno'].')
                                        </td>
                                        <td>'.(int)$item['itemqty'].'</td>
                                        <td>'.number_format($price,2).'</td>
                                        <td class="totalamount">'.(number_format($price * $item['itemqty'],2)).'</td>
                                    </tr>
                                ';
                            }
                            if($customertype !='Regular'){
                                $totaldiscount = $receiptdetails->payment_vatable_amount + $receiptdetails->payment_discount_amount;
                            }
                            
                            $html .='
                    </tbody>
                </table>
                <table class="print-item-table">
                    <tfoot class="tablefooter">
                        <tr>
                            <td colspan="2" width="400" >Subtotal </td>
                            <td colspan="2" class="right"><b>'.number_format($totalamountdue, 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2" width="400" >Total Discount</td>
                            <td colspan="2" class="right"><b>'.number_format($totaldiscount, 2).'</b></td>
                        </tr>
                         <tr>
                            <td colspan="2" width="400" >Total Amount Due </td>
                            <td colspan="2" class="right"><b>'.number_format(($receiptdetails->payment_total_amount), 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2" width="400">CASH TENDERED</td>
                            <td colspan="2" class="right">'.number_format($receiptdetails->payment_received_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td colspan="2" width="400">CHANGE</td>
                            <td colspan="2" class="right"><b>'.number_format($receiptdetails->payment_changed_amount, 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2" width="400" ></td>
                            <td colspan="2" class="right"><br></td>
                        </tr>
                        <tr>
                            <td colspan="2" width="400">No. of Item(s)</td>
                            <td colspan="2" class="right">'.$counter.'</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="left">Payment Type</td>
                            <td colspan="2" class="right">'.$receiptdetails->payment_method.'</td>
                        </tr>
                    </tfoot>
                </table>       
               
                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td width="70">Vatable Sales <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">VAT '.($possetting->vat_rate * 100).'%<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">VAT-Exempt Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_exempt_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">Zero-rated Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_zero_rated_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">LESS '. $istype.' DISCOUNT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.$lessvat.' '.number_format($receiptdetails->payment_discount_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">Total Sales <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format(($receiptdetails->payment_amount_due + $addvat), 2).'</td>
                        </tr>
                    </tfoot>
                </table>          

                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td class="leftfooter">CUSTOMER TYPE</td> 
                            <td class="rightfooter borderbottom">:'.$customertype.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">SOLD TO</td> 
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_name.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">ADDRESS</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_Address.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">TIN</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_TIN.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">BUSINESS STYLE</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_business_style.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">SIGNATURE</td>
                            <td class="rightfooter borderbottom">:</td>
                        </tr>
                    </tfoot>
                </table>
                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td class="textcenterfooter" colspan="2">SUPPLIER :</td>
                        </tr>  
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_company_name.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_address_bldg.' '.$possetting->bir_settings->pos_supplier_address_streetno.'</td>
                        </tr>
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_tin.'</td>
                        </tr>
                        <tr>
                            <td class="textcenterfooter" colspan="2">Acc. No:'.$possetting->bir_settings->bir_accreditation_number.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Date of Accreditation :'.Carbon::parse($possetting->bir_settings->bir_accreditation_date)->format('m/d/Y').'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Valid until :'.Carbon::parse($possetting->bir_settings->bir_accreditation_valid_until_date)->format('m/d/Y').'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">PTU :'.$possetting->bir_settings->bir_permit_to_use_number.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Date Issued :'.Carbon::parse($possetting->bir_settings->bir_permit_to_use_issued_date)->format('m/d/Y').'</td>
                        </tr> 
                    </tfoot>
                </table>
            </div>
        </div>
    ';
        return $html;
    }
    
    public function manual_generated_orprintout($order_id)
    {
        $receiptdetails = vwPaymentReceipt::where('order_id', $order_id)->first();
        $receiptitems = vwPaymentReceiptItems::where('order_id', $order_id)->get();
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $customertype = 'Regular';
        $addvat = $receiptdetails ? $receiptdetails->payment_vatable_amount : '0';
        $lessvat = '';
        $senior_id = '';
        $istype = 'SC/PWD';
        if($receiptdetails->isSeniorCitizen == '1' && $receiptdetails->isPWD == '0') {
            $customertype = 'Senior Discount';
            $senior_id =$receiptdetails->seniorID_No;
            $addvat = 0.00;
            $lessvat = '-';
        } elseif($receiptdetails->isSeniorCitizen == '0' && $receiptdetails->isPWD == '1') {
            $customertype = 'PWD Discount';
            $addvat = 0.00;
            $senior_id =$receiptdetails->PWD_No;
            $lessvat = '-';
        }


       
        $html = '
       
        <div   style="width:100%;margin-top:62px;">
            <table style="width:100%;font-size:12px !important;padding:0px !important;margin:0px !important;color:black !important;">
                <tr>
                    <td style="width:1.8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:62%;padding:0px !important;margin:0px !important;letter-spacing:0.020em;">
                        <div style="margin-left:10px;">'.strtoupper($receiptdetails->customer_name).'</div>
                    </td>
                    <td colspan="4" style="padding:0px !important;margin:0px !important;letter-spacing:0.020em;">
                    '.Carbon::parse($receiptdetails->payment_date)->format('m-d-Y h:m:i').' &nbsp; Order ID:'.$receiptdetails->pick_list_number.'
                    </td>
                </tr>
                <tr>
                    <td style="width:1.8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:59%;padding:0px !important;margin:0px !important;"><div style="margin-left:10px;">'.strtoupper($receiptdetails->customer_Address).'</div></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:18%;padding:0px !important;margin:0px !important;">'.$receiptdetails->customer_TIN.'</td>
                    <td style="width:4%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:4%;padding:0px !important;margin:0px !important;">'.$receiptdetails->customer_business_style.'</td>
                </tr>
            </table>
            <div  style="width:100%;margin-top:30px; height:180px;">
                <table  style="width:100%;font-size:12px !important;color:black !important;">
                    <tbody>
                        ';
                        $counter = 0;
                        $totalamountdue = 0;
                        $totaldiscount = 0;
                        foreach($receiptitems as $item) {
                            $counter++;
                            $totalamountdue += ($item['itemprice'] * $item['itemqty']);
                            if((float)$addvat == 0){
                             
                            }else{
                                $totaldiscount += ($item['itemprice'] - $item['itemcashprice']);
                            }
                            $html .='
                            <tr>
                                <td  style="width:10%;">'.(int)$item['itemid'].'</td>
                                <td style="width:30%;">
                                    <div >'.ucfirst($item['itemname']).' '.ucfirst($item['itemdescription']).'</div>
                                </td>
                                <td style="width:10%; text-align:center;" ></td>
                                <td style="width:10%; text-align:center;"  >'.(int)$item['itemqty'].'</td>
                                <td style="width:10%; text-align:center;"  >'.number_format($item['itemchargeprice'],2).'</td>
                                <td style="width:10%; text-align:center;"  >'.number_format(($item['order_item_sepcial_discount'] * $item['itemqty']),2).'</td>
                                <td style="width:15%; text-align:center;"  >'.number_format(($item['itemchargeprice'] * $item['itemqty']) ,2).'</td>
                                <td style="width:15%; text-align:center;"   >'.number_format($item['order_item_total_amount'] ,2).'</td>
                            </tr>
                        ';
                        }
                        $html .='
                        <tr>
                            <td colspan="8"><br>
                                <div style="letter-spacing: .2rem;"><center>**********NOTHING FOLLOWS**********</center></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div >
                <div style="display: inline-block; width: 35%;  "></div>
                <div style="display: inline-block; width:30%;  ">Cash Tendered : '.number_format($receiptdetails->payment_received_amount,2).'</div>
                <div style="display: inline-block; width:30%;  ">Changed: '.number_format($receiptdetails->payment_changed_amount,2).'</div>
            </div>
            <table style="width:100%;font-size:12px !important;margin-top:5px;color:black !important;">
                <tr>
                    <td style="width:3%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_vatable_sales_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_total_sales_vat_incl_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_amount_due, 2).'</td>
                </tr>
                <tr>
                    <td style="width:3%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_vatable_exempt_sales_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_vatable_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($addvat, 2).'</td>
                </tr>
                <tr>
                    <td style="width:3%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_zero_rated_sales_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_amount_net_of_vat, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;" rowspan="2">
                        <div style="font-size:19px !important;"> &#8369; '.number_format($receiptdetails->payment_total_amount, 2).'</div>
                    </td>
                </tr>
                <tr>
                    <td style="width:3%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;">'.number_format($receiptdetails->payment_vatable_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;"> '.number_format($receiptdetails->payment_discount_amount, 2).'</td>
                    <td style="width:8%;padding:0px !important;margin:0px !important;"></td>
                </tr>
        </table>
        <div style="width:100%;margin-top:22px;">
            <table style="width:100%;font-size:12px !important;color:black !important;">
                <tr>
                    <td style="width:22%;padding:0px !important;margin:0px !important;">
                        <div>'.$receiptdetails->pa_name.'</div><br>
                    </td>
                    <td style="width:20%;padding:0px !important;margin:0px !important;">'.$senior_id.'</td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;" rowspan="2"></td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;"  rowspan="2"></td>
                </tr>
                <tr>
                    <td style="width:22%;padding:0px !important;margin:0px !important;">
                        <div>'.$receiptdetails->cashier_name.'</div>
                    </td>
                    <td style="width:10%;padding:0px !important;margin:0px !important;"></td>
                   </tr>
            </table>
        </div>
        </div>
    ';
        return $html;
    }



    public function reprintreceipt(Request $request)
    {
        $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
        $pos_printout_layout = 0;
        if($series_setting->isSystem == 1 && $series_setting->isPos == 0) {
            $pos_printout_layout = 1;
        } elseif($series_setting->isSystem == 0 && $series_setting->isPos == 1) {
            $pos_printout_layout = 2;
        }
        $or_receipt = $this->orprintout(Request()->order_id);
        return response()->json(['or_receipt'=>$or_receipt,'pos_printout_layout'=>$pos_printout_layout], 200);
    }



    public function refundprintout($refund_id)
    {

        $receiptdetails = vwRefundReceipt::where('refund_id', $refund_id)->first();
        $receiptitems = vwReturnDetails::where('id', $refund_id)->get();
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $customertype = 'Regular';
        $addvat = $receiptdetails->payment_vatable_amount ?? '0';
        $lessvat = '';
        if($receiptdetails->isSeniorCitizen == '1' && $receiptdetails->isPWD == '0') {
            $customertype = 'Senior Discount';
            $addvat = 0.00;
            $lessvat = '-';
        } elseif($receiptdetails->isSeniorCitizen == '0' && $receiptdetails->isPWD == '1') {
            $customertype = 'PWD Discount';
            $addvat = 0.00;
            $lessvat = '-';
        }
        $html = '
            <div class="printout-company-details">
                <div class="printout-company-name">'.$possetting->company_name.'</div>
                <div class="printout-company-address">'.$possetting->company_address_bldg.' '.$possetting->company_address_streetno.'</div>
            </div>

            <div class="printout-company-bir-details">
                <div class="printout-company-tin">Vat Reg TIN: '.$possetting->company_tin.'</div>
                <div class="printout-company-min">MIN : '.$receiptdetails->terminal_Machine_Identification_Number.'</div>
                <div class="printout-seriesno">SN : '.$receiptdetails->terminal_serial_number.'/ TN :'.$receiptdetails->payment_id.'</div>
                <div class="printout-seriesno"> TR# '.$receiptdetails->payment_transaction_number.'</div>
            </div>

            <table  >
                <thead  class="header">
                    <tr>
                        <td class="si-td" colspan="3">SALES INVOICE NO.</td>
                        <td class="si-td" >:'.$receiptdetails->sales_invoice_number.'</td>
                    </tr>
                    <tr>
                       <td class="print-cashier" >Cashier</td>
                       <td class="print-cashier-name">: '.$receiptdetails->cashier_name.'</td>
                       <td class="print-date">Date</td>
                       <td class="print-date-value">: '.Carbon::parse($receiptdetails->payment_date)->format('m/d/Y').'</td>
                    </tr>
                    <tr>
                        <td class="print-pa">P.A</td>
                        <td class="print-pa-name">: '.$receiptdetails->pa_name.'</td>
                        <td class="print-time">Time</td>
                        <td class="print-time-value">: '.Carbon::parse($receiptdetails->payment_date)->format('H:i:s').'</td>
                    </tr>
                </thead>
            </table>

            <div class="print-item">
                <table class="print-item-table">
                    <tbody>
                        <tr class="thead">
                            <th class="product">PRODUCT</th>
                            <th class="productqty">QTY</th>
                            <th class="productprice">PRICE</th>
                            <th class="totalamount">AMOUNT</th>
                        </tr>
                        ';
        $counter = 0;
        $totalamountdue = 0;
        foreach($receiptitems as $item) {

            $qty = 0;
            $price = 0;
            $totalorder = 0;
            $totalreturn = 0;
            $totalamount =0;
            if($item->type == 'return') {
                $qty = '-'.(int)$item['qty'].'';
                $price = (float)$item['price'].'';
                $totalreturn = $item['price'] * $item['qty'];
                $totalamount ='-'.(float)($item['price'] * $item['qty']).'';
            } elseif($item->type == 'order') {
                $qty = (int)$item['qty'];
                $price = (float)$item['price'];
                $totalorder = $item['price'] * $item['qty'];
                $totalamount =(float)($item['price'] * $item['qty']);
                $counter++;
            }

            $totalamountdue +=$totalorder - $totalreturn;
            $html .='
                                    <tr>
                                        <td>
                                         <div>'.$item['item_name'].' '.$item['item_description'].'</div>
                                        ('.$item['item_batchno'].')
                                        </td>
                                        <td>'.(int)$qty.' </td>
                                        <td>'.number_format($price,2).'</td>
                                        <td class="totalamount">'.number_format($totalamount,2).'</td>
                                    </tr>
                                ';
        }

        $html .='
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="left">Total Amount Due </td>
                            <td colspan="2" class="right"><b>'.(float)$totalamountdue.'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="left">PAYMENT</td>
                            <td colspan="2" class="right">'.(float)$receiptdetails->payment_received_amount.'</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="left">CHANGE DUE</td>
                            <td colspan="2" class="right"><b>'.(float)$receiptdetails->payment_changed_amount.'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="left">No. of Item(s)</td>
                            <td colspan="2" class="right">'.$counter.'</td>
                        </tr>
                    </tfoot>
                </table>

                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td width="120">VAT Sales <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">VAT-Exempt Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_exempt_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">Zero-rated Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_zero_rated_sales_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">VAT AMOUNT'.($possetting->vat_rate * 100).'%<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">Total Sale (VAT INC) <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_total_sales_vat_incl_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">LESS VAT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.$lessvat.''.number_format($receiptdetails->payment_vatable_amount, 2).'</td>
                        </tr>
                        
                        <tr>
                            <td width="50">AMOUNT NET OF VAT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_amount_net_of_vat, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">LESS DISCOUNT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.$lessvat.''.number_format($receiptdetails->payment_discount_amount, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">AMOUNT DUE <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_amount_due, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">ADD VAT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($addvat, 2).'</td>
                        </tr>
                        <tr>
                            <td width="50">TOTAL AMOUNT <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_total_amount, 2).'</td>
                        </tr>
                    </tfoot>
                </table>          

                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td class="leftfooter">CUSTOMER TYPE</td> 
                            <td class="rightfooter borderbottom">:'.$customertype.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">SOLD TO</td> 
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_name.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">ADDRESS</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_Address.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">TIN</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_TIN.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">BUSINESS STYLE</td>
                            <td class="rightfooter borderbottom">:'.$receiptdetails->customer_business_style.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">SIGNATURE</td>
                            <td class="rightfooter borderbottom">:</td>
                        </tr>
                    </tfoot>
                </table>
                <table class="print-item-table ">
                    <tfoot  class="tablefooter">
                        <tr>
                            <td class="textcenterfooter" colspan="2">SUPPLIER :</td>
                        </tr>  
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_company_name.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_address_bldg.' '.$possetting->bir_settings->pos_supplier_address_streetno.'</td>
                        </tr>
                        <tr>
                            <td class="textcenterfooter" colspan="2">'.$possetting->bir_settings->pos_supplier_tin.'</td>
                        </tr>
                        <tr>
                            <td class="textcenterfooter" colspan="2">Acc. No:'.$possetting->bir_settings->bir_accreditation_number.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Date of Accreditation :'.Carbon::parse($possetting->bir_settings->bir_accreditation_date)->format('m/d/Y').'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Valid until :'.Carbon::parse($possetting->bir_settings->bir_accreditation_valid_until_date)->format('m/d/Y').'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">PTU :'.$possetting->bir_settings->bir_permit_to_use_number.'</td>
                        </tr> 
                        <tr>
                            <td class="textcenterfooter" colspan="2">Date Issued :'.Carbon::parse($possetting->bir_settings->bir_permit_to_use_issued_date)->format('m/d/Y').'</td>
                        </tr> 
                    </tfoot>
                </table>
              
            </div>
        ';
        return $html;
    }

    public function printRefund(Request $request)
    {
        $or_receipt = $this->refundprintout(Request()->refundid);
        return response()->json(['refund_receipt'=>$or_receipt], 200);
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
        //
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
