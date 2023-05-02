<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\POS\Orders;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\POSSetting;
use App\Models\BuildFile\Systerminals;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Helpers\PosSearchFilter\Orderlist;
use App\Helpers\PosSearchFilter\UserDetails;

class OrdersController extends Controller
{
    public function index()
    {
        $data = (new Orderlist())->searchable();
        return response()->json(["data"=>$data,"message" => 'success' ], 200);
    }
   
    public function store(Request $request){
       
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $terminal = (new Terminal)->terminal_details();
            $sequenceno = (new SeriesNo())->get_sequence('PPLN',$terminal->terminal_code);
            $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
            if($sequenceno->isSystem == '0'){
                $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
            }
            $orders = Orders::create([
              'branch_id'=> Auth()->user()->branch_id,
              'warehouse_id'=> Auth()->user()->warehouse_id,
              'customer_id'=>Request()->payload['customer_payload']['id'] ?? '',
              'pick_list_number'=> $generatesequence,
              'order_date'=> Carbon::now(),
              'order_total_line_item_ordered'=> count(Request()->payload['cart_items']),
              'order_vatable_sales_amount'=> Request()->payload['cart_footer']['vatsales'],
              'order_vatexempt_sales_amount'=> Request()->payload['cart_footer']['vatexemptsale'],
              'order_zero_rated_sales_amount'=> Request()->payload['cart_footer']['zeroratedsale'],
              'order_total_sales_vat_incl_amount'=> Request()->payload['cart_footer']['totalsalesvatinclude'],
              'order_vat_amount'=> Request()->payload['cart_footer']['vatamount'],
              'order_vat_net_amount'=> Request()->payload['cart_footer']['amountnetvat'],
              'order_senior_citizen_amount'=> Request()->payload['cart_footer']['lessdiscount'],
              'order_due_amount'=> Request()->payload['cart_footer']['amountdue'],
              'order_total_payment_amount'=> Request()->payload['cart_footer']['totalamount'],
              'pa_userid'=>Auth()->user()->id,
              'cashier_user_id'=>0,
              'checker_userid'=>0,
              'terminal_id'=>$terminal->id,
              'order_status_id'=>7,
              'createdBy'=>Auth()->user()->id,
            ]);
            foreach (Request()->payload['cart_items'] as $row) {
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
                    'isReturned'=>'0',
                    'createdBy'=>Auth()->user()->id,
                ]);
               
            }
            if ($sequenceno->isSystem == '0') {
                $sequenceno->update([
                    'manual_seq_no'=>(int)$sequenceno->manual_seq_no + 1,
                    'manual_recent_generated'=>$generatesequence,
                ]);
            }else{

                $sequenceno->update([
                    'seq_no'=>(int)$sequenceno->seq_no + 1,
                    'recent_generated'=>$generatesequence,
                ]);
            }
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            $pa_userid = Auth()->user()->id;
            $picklist = $this->picklistprintout(Request()->payload['cart_items'],Request()->payload['customer_payload'],$generatesequence,$pa_userid);

            return response()->json(["message" => 'Record successfully saved','status'=>'200','picklist'=>$picklist,'picklistno'=>$generatesequence], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id){
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $orders = Orders::where('id', $id)->first();
            $orders->where('id',$id)->update([
              'branch_id'=> Auth()->user()->branch_id,
              'warehouse_id'=> Auth()->user()->warehouse_id,
              'customer_id'=>Request()->payload['customer_payload']['id'] ?? '',
              'pick_list_number'=>Request()->payload['orderdetails']['orderpicklistno'] ?? '',
              'order_date'=> Carbon::now(),
              'order_total_line_item_ordered'=> count(Request()->payload['cart_items']),
              'order_vatable_sales_amount'=> Request()->payload['cart_footer']['vatsales'],
              'order_vatexempt_sales_amount'=> Request()->payload['cart_footer']['vatexemptsale'],
              'order_zero_rated_sales_amount'=> Request()->payload['cart_footer']['zeroratedsale'],
              'order_total_sales_vat_incl_amount'=> Request()->payload['cart_footer']['totalsalesvatinclude'],
              'order_vat_amount'=> Request()->payload['cart_footer']['vatamount'],
              'order_vat_net_amount'=> Request()->payload['cart_footer']['amountnetvat'],
              'order_senior_citizen_amount'=> Request()->payload['cart_footer']['lessdiscount'],
              'order_due_amount'=> Request()->payload['cart_footer']['amountdue'],
              'order_total_payment_amount'=> Request()->payload['cart_footer']['totalamount'],
              'pa_userid'=>Auth()->user()->id,
              'terminal_id'=>Request()->payload['terminalid'] ?? '1',
              'order_status_id'=>7,
              'updatedBy'=>Auth()->user()->id,
            ]);
            $itemid = [];
            foreach (Request()->payload['cart_items'] as $row) {
                $itemid[] = $row['id'];
                if(isset($row['order_id'])){
                    $orders->order_items()->where('id',$row['order_id'])->update([
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
                        'isReturned'=>'0',
                        'isDeleted'=>'0',
                        'updatedBy'=>Auth()->user()->id,
                    ]);
                }else{
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
                        'isReturned'=>'0',
                        'isDeleted'=>'0',
                        'updatedBy'=>Auth()->user()->id,
                    ]);
                }
            }
            $checkorderitems = $orders->order_items()->where('order_id',$id)->whereNotIn('order_item_id',json_decode(json_encode($itemid)))->get();
            foreach($checkorderitems as $row){
                $orders->order_items()->where('id',$row['id'])->delete();
            }
            DB::connection('sqlsrv_pos')->commit();    
            $pa_userid = Auth()->user()->id;
            $picklist = $this->picklistprintout(Request()->payload['cart_items'],Request()->payload['customer_payload'],Request()->payload['orderdetails']['orderpicklistno'],$pa_userid);
            return response()->json(["message" => 'Record successfully saved','status'=>'200','picklist'=>$picklist,'picklistno'=>Request()->payload['orderdetails']['orderpicklistno']], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }

    public function cancelorder(Request $request){
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $orders = Orders::where('id', $request->id)->first();
            $orders->update(['order_status_id'=>'8']);
            DB::connection('sqlsrv_pos')->commit();    
            return response()->json(["message" => 'Record successfully cancel','status'=>'200'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }   

    public function picklistprintout($items,$customerdetails,$picklistno,$pa_userid){
        $possetting = POSSettings::with('bir_settings')->where('isActive','1')->first();
        $user = (new UserDetails)->userdetails($pa_userid);
        $html = '
            <div class="printout-company-details">
                <div class="printout-company-name">'.$possetting->company_name.'</div>
                <div class="printout-company-address">'.$possetting->company_address_bldg.' '.$possetting->company_address_streetno.'</div>
            </div>
            <table>
                <thead  class="header">
                    <tr>
                        <td >Customer</td>
                        <td colspan="3">: '.ucfirst($customerdetails['customer_last_name']).', '.ucfirst($customerdetails['customer_first_name']).' '.ucfirst($customerdetails['customer_middle_name']).'
                        </td>
                    </tr>
                    <tr>
                        <td >Type</td>
                        <td colspan="3">: '.ucfirst($customerdetails['discounttype']).'
                        </td>
                    </tr>
                    <tr>
                        <td  >Order ID #</td>
                        <td  colspan="3">: '.$picklistno.'</td>
                    </tr>
                    <tr>
                        <td>P.A</td>
                        <td>: '.ucfirst($user->lastname).', '.ucfirst($user->firstname).' '.ucfirst($user->middlename).'</td>
                        <td class="print-date">Date</td>
                        <td class="print-date-value">: '.Carbon::now()->format('m/d/Y').'</td>
                    </tr>
                </thead>
            </table>
            <div class="print-item">
                <table class="print-item-table">
                    <tbody>
                        <tr class="thead">
                            <th colspan="3">PARTICULARS</th>
                            <th colspan="1"><center>QTY</center></th>
                        </tr>
                        ';  
                        $counter = 0;
                        foreach($items as $item){
                            $counter++;
                            $html .='
                                <tr>
                                    <td colspan="4">
                                        <div class="mt-1 itemname">'.$item['itemname'].' '.$item['itemdescription'].'</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="batchno batchexpire">Batch # : ('.$item['itembatchno']['batch_Number'].')</div>
                                        <div class="batchexpire">Exp : '.Carbon::parse($item['itembatchno']['item_Expiry_Date'])->format('Y-m-d') .'</div>
                                    </td>
                                    <td colspan="2"><center>'.$item['itemqty'].'</center></td>
                                </tr>
                            ';
                        }
                        
                        $html .='
                        <tr class="thead">
                            <th colspan="3">NO OF ITEM(S)</th>
                            <th colspan="1"><center>'.$counter.'</center></th>
                        </tr>
                    </tbody>
                </table>
            </div>
        ';
        return $html;
    }

    public function reprintpicklist(Request $request){

        $picklistno = Request()->payload['orderpicklistno'] ?? '';
        $pa_userid = Request()->payload['pa_userid'] ?? '';
        $picklist = $this->picklistprintout(Request()->payload['items'],Request()->payload['customer_payload'],$picklistno,$pa_userid);
        return response()->json(["message" => 'Record successfully saved','status'=>'200','picklist'=>$picklist,'picklistno'=>$picklistno], 200);

    }
    public function destroy($id){
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $orders = Orders::where('id', $id)->first();

        } catch (\Exception $e) {

            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    
    }
}
