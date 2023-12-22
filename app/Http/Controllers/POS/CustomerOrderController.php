<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\POS\Orders;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use App\Models\POS\vwCustomers;
use App\Http\Controllers\Controller;
use App\Models\POS\vwCustomerOrders;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Helpers\PosSearchFilter\UserDetails;
use App\Helpers\PosSearchFilter\FloatConverter;

class CustomerOrderController extends Controller
{
    protected $orders_data = [];
    protected $item_details = [];
    protected $json_file ='';

    public function __construct()
    {

    }
    public function save_orders(Request $request){
      
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $terminal = (new Terminal())->terminal_details();
            $sequenceno = (new SeriesNo())->get_sequence('PPLN', $terminal->terminal_code);
            $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
            if($sequenceno->isSystem == '0') {
                $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
            }

            $orders = Orders::create([
                'branch_id'=> Auth()->user()->branch_id,
                'warehouse_id'=> Auth()->user()->warehouse_id,
                'customer_id'=>Request()->customer_details['id'] ?? '',
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
                'order_other_discount_amount'=> Request()->order_computation['order_other_discount_amount'],
                'pa_userid'=>Auth()->user()->idnumber,
                'cashier_user_id'=>0,
                'checker_userid'=>0,
              //   'terminal_id'=>$terminal->id,
                'terminal_id'=>$terminal->id,
                'take_order_terminal_id'=>$terminal->terminal_Id,
                'order_status_id'=>7,
                'createdBy'=>Auth()->user()->idnumber,
              ]);
              foreach (Request()->orders as $row) {

                 
                  $orders->order_items()->create([
                      'order_item_id'=>$row['order_item_id'],
                      'order_item_qty'=>$row['order_item_qty'],
                      'order_item_charge_price'=>(new FloatConverter())->value($row['order_item_price']),
                      'order_item_cash_price'=>(new FloatConverter())->value($row['order_item_cash_price']),
                      'order_item_price'=>(new FloatConverter())->value($row['order_item_price'] - $row['order_item_sepcial_discount']),
                      'order_item_vat_rate'=>(new FloatConverter())->value($row['order_item_vat_rate']),
                      'order_item_vat_amount'=>(new FloatConverter())->value($row['order_item_vat_amount']),
                      'order_item_sepcial_discount'=>(new FloatConverter())->value($row['order_item_sepcial_discount']),
                      'order_item_discount_amount'=>(new FloatConverter())->value($row['order_other_discount_amount']),
                      // 'order_item_discount_amount'=>(new FloatConverter())->value($row['itemtotaldiscount']),
                      'order_item_total_amount'=> (new FloatConverter())->value($row['order_total_payment_amount']),
                      'order_item_batchno'=>$row['item_batchno']['id'],
                      'isReturned'=>'0',
                      'createdBy'=>Auth()->user()->idnumber,
                  ]);
              }

            if ($sequenceno->isSystem == '0') {
                $sequenceno->update([
                    'manual_seq_no'=>(int)$sequenceno->manual_seq_no + 1,
                    'manual_recent_generated'=>$generatesequence,
                ]);
            } else {

                $sequenceno->update([
                    'seq_no'=>(int)$sequenceno->seq_no + 1,
                    'recent_generated'=>$generatesequence,
                ]);
            }
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            $pa_userid = Auth()->user()->idnumber;
            $picklist = $this->picklistprintout($orders->id, Request()->customer_details, $generatesequence, $pa_userid);

            return response()->json(["message" => 'Record successfully saved','status'=>'200','picklist'=>$picklist,'picklistno'=>$generatesequence], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }
    public function picklistprintout($orderid, $customer_details, $picklistno, $pa_userid)
    {
        $user = (new UserDetails())->userdetails($pa_userid);
        $items = vwCustomerOrders::where('order_id',$orderid)->where('customer_id',$customer_details['id'])->get();
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $customerdetails = vwCustomers::where('id',$customer_details['id'])->where('isActive', '1')->first();
        $html = '
            <div class="printout-company-details">
                <div class="printout-company-name">'.$possetting->company_name.'</div>
                <div class="printout-company-address">'.$possetting->company_address_bldg.' '.$possetting->company_address_streetno.'</div>
            </div>
            <table>
                <thead  class="header">
                    <tr>
                        <td >Customer</td>
                        <td colspan="3">: '.ucfirst($customerdetails['name']).'
                        </td>
                    </tr>
                    <tr>
                        <td  >Order ID #</td>
                        <td  colspan="3">: '.$picklistno.'</td>
                        <td class="print-date">Type</td>
                        <td class="print-date-value" width="250">: '.ucfirst($customerdetails['group_name']).'</td>
                    </tr>
                    <tr>
                        <td  >Date</td>
                        <td  colspan="3">: '.Carbon::now()->format('m/d/Y H:i A').'</td>
                    </tr>
                    <tr>
                        <td>P.A</td>
                        <td colspan="3">: '.ucfirst($user->lastname).', '.ucfirst($user->firstname).' '.ucfirst($user->middlename).'</td>
                    </tr>
                </thead>
            </table>
            <div class="print-item">
                <table class="print-item-table">
                    <tbody>
                        <tr class="thead">
                            <th colspan="1"><center>#</center></th>
                            <th colspan="3">PARTICULARS</th>
                            <th colspan="1"><center>QTY</center></th>
                        </tr>
                        ';
                        $counter = 0;
                        foreach($items as $item) {
                            $counter++;
                            $html .='
                                    <tr>
                                        <td>'.$counter.'. </td>
                                        <td colspan="4">
                                            <div class="mt-1 ml-2 itemname">&nbsp;'. $item['item_name'] .' '. $item['item_description'].'</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2">
                                            <div class="batchno batchexpire">Batch # : '.$item['order_item_batchno'] .'</div>
                                            <div class="batchexpire">Exp : '.Carbon::parse($item['item_Expiry_Date'])->format('Y-m-d') .'</div>
                                        </td>
                                        <td colspan="2"><center>'.(int)$item['order_item_qty'].'</center></td>
                                    </tr>
                                ';
                            }

                        $html .='
                        <tr class="thead">
                            <th colspan="4">NO OF ITEM(S)</th>
                            <th colspan="1"><center>'.$counter.'</center></th>
                        </tr>
                    </tbody>
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

    function getOrders(){
        $orderID = Request()->orderid ?? '';
        $customerID = Request()->customerid ?? '';
        $data['data'] = vwCustomerOrders::where('order_id',$orderID)->where('customer_id',$customerID)->get();
        return response()->json($data, 200);
    }

}
