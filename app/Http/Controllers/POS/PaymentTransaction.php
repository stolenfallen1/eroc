<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\POS\Orders;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\OrderItems;

use App\Models\POS\POSSettings;
use App\Models\POS\vwRefundReceipt;
use App\Models\POS\vwReturnDetails;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\POSSetting;
use App\Models\POS\vwPaymentReceipt;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\vwPaymentReceiptItems;
use App\Helpers\PosSearchFilter\UserDetails;

class PaymentTransaction extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
    public function store(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $terminal = (new Terminal)->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI',$terminal->terminal_code);
            $tran_sequenceno =(new SeriesNo())->get_sequence('PTN',$terminal->terminal_code);
            
            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0'){
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }

            $paymenttype = Request()->payload['paymenttype'];
            $totalamount = Request()->payload['amounttendered'];
            $cardtype = '';
            if($paymenttype !="1"){
                $totalamount = Request()->payload['totalamount'];
                $cardtype = Request()->payload['cardtype'];
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
              'payment_discount_amount'=>(float)Request()->payload['cart_footer']['lessdiscount'] ?? '',
              'payment_amount_due'=>(float)Request()->payload['cart_footer']['amountdue'] ?? '',
              'payment_received_amount'=>(float)$totalamount ?? '0',
              'payment_changed_amount'=>(float)Request()->payload['change'] ?? '',
              'payment_total_amount'=>(float)(Request()->payload['cart_footer']['amountdue'] + Request()->payload['cart_footer']['vatamount']),
              'payment_refund_amount'=> '0',
              'terminal_id'=>Request()->payload['terminalid'] ?? '1',
              'user_id'=>Auth()->user()->id,
              'shift_id'=>Auth()->user()->shift,
              'createdBy'=>Auth()->user()->id,
            ]);

            $orders = Orders::where('id',Request()->payload['orderid']);
            $orders->update([
                'order_status_id'=>'9'
            ]);

            foreach (Request()->payload['orderitems'] as $row) {
                $batch = ItemBatch::where('id',(int)$row['itembatchno']['id'])->first();
                $warehouseitem = Warehouseitems::where('id',(int)$row['id'])->where('branch_id',(int)Auth()->user()->branch_id)->where('warehouse_id',(int)Auth()->user()->warehouse_id)->first();
                if($batch){
                    $isConsumed = '0';
                    $usedqty = $batch->item_Qty_Used + $row['itemqty'];
                    if($usedqty >= $batch->item_Qty){
                        $isConsumed = '1';
                    }
                    $batch->item_Qty_Used += (int)$row['itemqty'];
                    $batch->isConsumed = $isConsumed;
                    $batch->save();
                }
                if($warehouseitem){
                    $warehouseitem->item_OnHand -= (int)$row['itemqty'];
                    $warehouseitem->save();
                }
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
           
            $transaction['ornumber'] = $generat_or_series;
            $transaction['transid'] = $payment->id;
            $transaction['transno'] = $payment->payment_transaction_number;
            // $or_receipt = $this->orprintout(Request()->payload,$transaction);
            $or_receipt = $this->orprintout(Request()->payload['orderid']);

            return response()->json(["message" =>  'Record successfully saved','status'=>'200','or_receipt'=>$or_receipt], 200);
        } catch (\Exception $e) {

            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    public function orprintout($order_id){
        
        $receiptdetails = vwPaymentReceipt::where('order_id',$order_id)->first(); 
        $receiptitems = vwPaymentReceiptItems::where('order_id',$order_id)->get(); 
        $possetting = POSSettings::with('bir_settings')->where('isActive','1')->first();
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
                                $counter = 0;
                                $totalamountdue = 0;
                                foreach($receiptitems as $item){
                                    $counter++;
                                    $totalamountdue += ($item['itemcashprice'] * $item['itemqty']);
                                    $html .='
                                        <tr>
                                            <td colspan="4"> 
                                            '.$item['itemname'].' '.$item['itemdescription'].'
                                            <div>'.$item['brand'].'</div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>('.$item['order_item_batchno'].')</td>
                                            <td>'.(int)$item['itemqty'].' @</td>
                                            <td>'.(float)$item['itemcashprice'].'</td>
                                            <td class="totalamount">'.(float)($item['itemcashprice'] * $item['itemqty']).'</td>
                                        </tr>
                                    ';
                                }
                                
                            $html .='
                        </tbody>
                    </table>
                    <table class="print-item-details">
                        <tfoot class="tablefooter">
                             <tr>
                                <td colspan="2" width="400" >Total Amount Due </td>
                                <td colspan="2" class="right"><b>'.number_format($totalamountdue,2).'</b></td>
                            </tr>
                            <tr>
                                <td colspan="2" width="400">PAYMENT</td>
                                <td colspan="2" class="right">'.number_format($receiptdetails->payment_received_amount,2).'</td>
                            </tr>
                            <tr>
                                <td colspan="2" width="400">CHANGE DUE</td>
                                <td colspan="2" class="right"><b>'.number_format($receiptdetails->payment_changed_amount,2).'</b></td>
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
                     <table class="print-item-table">
                        <tfoot  class="tablefooter">
                            <tr>
                                <td width="50">Vatable Sales <span class="floatright">:</span></td>
                                <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_sales_amount,2).'</td>
                            </tr>
                            <tr>
                                <td width="50">VAT '.($possetting->vat_rate * 100).'%<span class="floatright">:</span></td>
                                <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_amount,2).'</td>
                            </tr>
                            <tr>
                                <td width="50">VAT-Exempt Sales<span class="floatright">:</span></td>
                                <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_vatable_exempt_sales_amount,2).'</td>
                            </tr>
                            <tr>
                                <td width="50">Zero-rated Sales<span class="floatright">:</span></td>
                                <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_zero_rated_sales_amount,2).'</td>
                            </tr>
                            <tr>
                                <td width="50">Total Sales (PHP)<span class="floatright">:</span></td>
                                <td class="rightfooter righttextfooter">'.number_format($receiptdetails->payment_total_amount,2).'</td>
                            </tr>
                        </tfoot>
                    </table>
                    <table class="print-item-table ">
                        <tfoot  class="tablefooter">
                            <tr>
                                <td class="leftfooter">SOLD TO</td> 
                                <td class="rightfooter">:'.$receiptdetails->customer_name.'</td>
                            </tr>
                            <tr>
                                <td class="leftfooter">ADDRESS</td>
                                <td class="rightfooter">:'.$receiptdetails->customer_Address.'</td>
                            </tr>
                            <tr>
                                <td class="leftfooter">TIN</td>
                                <td class="rightfooter">:'.$receiptdetails->customer_TIN.'</td>
                            </tr>
                            <tr>
                                <td class="leftfooter">BUSINESS STYLE</td>
                                <td class="rightfooter">:'.$receiptdetails->customer_business_style.'</td>
                            </tr>
                            <tr>
                                <td class="leftfooter">SIGNATURE</td>
                                <td class="rightfooter">:</td>
                            </tr>
                        </tfoot>
                    </table>
                    <table class="print-item-table ">
                    <tfoot  class="tablefooter">
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
 

    public function reprintreceipt(Request $request){
        $or_receipt = $this->orprintout(Request()->payload['orderid']);
        return response()->json(['or_receipt'=>$or_receipt], 200);
    }



    public function refundprintout($refund_id){
        
        $receiptdetails = vwRefundReceipt::where('refund_id',$refund_id)->first(); 
        $receiptitems = vwReturnDetails::where('id',$refund_id)->get(); 
        $possetting = POSSettings::with('bir_settings')->where('isActive','1')->first();
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
                    <thead class="header">
                        <tr>
                            <th class="product">PRODUCT</th>
                            <th class="productqty">QTY</th>
                            <th class="productprice">PRICE</th>
                            <th class="totalamount">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        ';  
                            $counter = 0;
                            $totalamountdue = 0;
                            foreach($receiptitems as $item){
                              
                                $qty = 0;
                                $price = 0;
                                $totalorder = 0;
                                $totalreturn = 0;
                                $totalamount =0;
                                if($item->type == 'return'){
                                    $qty = '-'.(int)$item['qty'].'';
                                    $price = (float)$item['price'].'';
                                    $totalreturn = $item['price'] * $item['qty'];
                                    $totalamount ='-'.(float)($item['price'] * $item['qty']).'';
                                }else if($item->type == 'order'){
                                    $qty = (int)$item['qty'];
                                    $price = (float)$item['price'];
                                    $totalorder = $item['price'] * $item['qty'];
                                    $totalamount =(float)($item['price'] * $item['qty']);
                                    $counter++;
                                }
                                
                                $totalamountdue +=$totalorder - $totalreturn;
                                $html .='
                                    <tr>
                                        <td colspan="4">
                                        '.$item['item_name'].' '.$item['item_description'].'
                                        <div>'.$item['brand'].'</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>('.$item['item_batchno'].')</td>
                                        <td>'.$qty.' </td>
                                        <td>'.$price.'</td>
                                        <td class="totalamount">'.$totalamount.'</td>
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
                    <tfoot>
                        <tr>
                            <td class="leftfooter">Vatable Sales <span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.(float)$receiptdetails->payment_vatable_sales_amount.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">VAT '.($possetting->vat_rate * 100).'%<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.(float)$receiptdetails->payment_vatable_amount.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">VAT-Exempt Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.(float)$receiptdetails->payment_vatable_exempt_sales_amount.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">Zero-rated Sales<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.(float)$receiptdetails->payment_zero_rated_sales_amount.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">Total Sales (PHP)<span class="floatright">:</span></td>
                            <td class="rightfooter righttextfooter">'.(float)$receiptdetails->payment_total_amount.'</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="footerbreak table-customerdetails"><br></td>
                        </tr>

                        <tr>
                            <td class="leftfooter">SOLD TO</td> 
                            <td class="rightfooter">:'.$receiptdetails->customer_name.'</td>
                        </tr>
                    
                        <tr>
                            <td class="leftfooter">ADDRESS</td>
                            <td class="rightfooter">:'.$receiptdetails->customer_Address.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">TIN</td>
                            <td class="rightfooter">:'.$receiptdetails->customer_TIN.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">BUSINESS STYLE</td>
                            <td class="rightfooter">:'.$receiptdetails->customer_business_style.'</td>
                        </tr>
                        <tr>
                            <td class="leftfooter">SIGNATURE</td>
                            <td class="rightfooter">:</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="table-customerdetails"><br></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="footerbreak table-customerdetails"><br><br><br></td>
                        </tr>
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
                        <tr>
                            <td colspan="2" class="table-customerdetails"><br><br></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        ';
        return $html;
    }

    public function printRefund(Request $request){
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
