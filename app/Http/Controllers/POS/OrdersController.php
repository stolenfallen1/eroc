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
use App\Helpers\PosSearchFilter\Orderlist;
use App\Helpers\PosSearchFilter\UserDetails;

class OrdersController extends Controller
{
    protected $orders_data = [];
    protected $item_details = [];
    protected $json_file ='';

    public function __construct()
    {

    }
    public function index()
    {
        $data = (new Orderlist())->searchable();
        return response()->json(["data"=>$data,"message" => 'success' ], 200);
    }

    public function cancelorder(Request $request)
    {
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

    public function picklistprintout($items, $customer_details, $picklistno, $pa_userid)
    {
        $user = (new UserDetails())->userdetails($pa_userid);

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

                            $itemname = isset($item['itemname']) ? $item['itemname'] : $item['vw_item_details']['item_name'];
                            $itemdescription = isset($item['itemdescription']) ? $item['itemdescription'] : $item['vw_item_details']['item_Description'];
                            $itembatchno = isset($item['itembatchno']['batch_Number'])   ? $item['itembatchno']['batch_Number'] : $item['item_batch']['batch_Number'];
                            $expiry = isset($item['itembatchno']['item_Expiry_Date'])  ? $item['itembatchno']['item_Expiry_Date'] : $item['item_batch']['item_Expiry_Date'];
                            $qty =isset($item['item_item_qty'])  ? $item['item_item_qty'] : $item['order_item_qty'];
                            $counter++;
                            $html .='
                                    <tr>
                                        <td>'.$counter.'. </td>
                                        <td colspan="4">
                                            <div class="mt-1 ml-2 itemname">&nbsp;'. $itemname .' '. $itemdescription.'</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2">
                                            <div class="batchno batchexpire">Batch # : '.$itembatchno .'</div>
                                            <div class="batchexpire">Exp : '.Carbon::parse($expiry)->format('Y-m-d') .'</div>
                                        </td>
                                        <td colspan="2"><center>'.$qty.'</center></td>
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

    public function reprintpicklist(Request $request)
    {
        $picklistno = Request()->payload['orderpicklistno'] ?? '';
        $pa_userid = Request()->payload['pa_userid'] ?? '';
        $picklist = $this->picklistprintout(Request()->payload['items'], Request()->payload['customer_payload'], $picklistno, $pa_userid);
        return response()->json(["message" => 'Record successfully saved','status'=>'200','picklist'=>$picklist,'picklistno'=>$picklistno], 200);
    }

    public function destroy($id)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        try {
            $orders = Orders::where('id', $id)->first();

        } catch (\Exception $e) {

            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);

        }

    }

}
