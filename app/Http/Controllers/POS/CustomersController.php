<?php

namespace App\Http\Controllers\POS;

use DB;
use Illuminate\Http\Request;
use App\Models\POS\Customers;
use App\Models\POS\vwCustomers;
use App\Models\POS\CustomerGroup;
use App\Http\Controllers\Controller;
use App\Models\POS\CustomerGroupMapping;
use App\Helpers\PosSearchFilter\Customer;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customergroup = CustomerGroup::all();
        $defaultcustomer = vwCustomers::where('group_id', 1)->where('isDefault',1)->first();
        $data = (new Customer())->searchable();
        return response()->json(["data"=>$data,"customergroup"=>$customergroup,"defaultcustomer"=>$defaultcustomer,"message" => "success"], 200);
    }

    public function default(Request $request)
    {
        $default = vwCustomers::where('group_id', 1)->where('isDefault',1)->first();
        $data['defaultcustomer'] = $default;
        // $data['defaultorder'] = vwCustomerOrders::where('customer_id',$default['id'])->whereDate('order_date',date('Y-m-d'))->get();
        return response()->json($data, 200);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
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
        try{
            if (Request()->payload['type'] == 'new') {
                $customers = Customers::create([
                    'customer_last_name' => Request()->payload['customer_last_name'] ?? '',
                    'customer_first_name' => Request()->payload['customer_first_name'] ?? '',
                    'customer_middle_name' => Request()->payload['customer_middle_name'] ?? '',
                    'customer_Address' => Request()->payload['customer_Address'] ?? '',
                    'isSeniorCitizen' => Request()->payload['isSeniorCitizen'] ?? '0',
                    'seniorID_No' => Request()->payload['seniorID_No'] ?? '',
                    'customer_TIN' => Request()->payload['customer_TIN'] ?? '',
                    'customer_business_style' => Request()->payload['customer_business_style'] ?? '',
                    'isPWD' => Request()->payload['isPWD'] ?? '0',
                    'PWD_No' => Request()->payload['PWD_No'] ?? '',
                    'isActive' => '1',
                    'createdBy' => Auth()->user()->idnumber,
                ]);
                $customers->customer_mapping()->create([
                    'customer_id'=>$customers->id,
                    'group_id'=>Request()->payload['customergroup'] ?? '',
                    'isActive' => '1',
                ]);
                
            } elseif (Request()->payload['type'] == 'update') {

                $customers = Customers::where('id',Request()->payload['id'])->first();
                $customers->update([
                    'customer_last_name' => Request()->payload['customer_last_name'] ?? '',
                    'customer_first_name' => Request()->payload['customer_first_name'] ?? '',
                    'customer_middle_name' => Request()->payload['customer_middle_name'] ?? '',
                    'customer_Address' => Request()->payload['customer_Address'] ?? '',
                    'isSeniorCitizen' => Request()->payload['isSeniorCitizen'] ?? '0',
                    'seniorID_No' => Request()->payload['seniorID_No'] ?? '',
                    'customer_TIN' => Request()->payload['customer_TIN'] ?? '',
                    'customer_business_style' => Request()->payload['customer_business_style'] ?? '',
                    'isPWD' => Request()->payload['isPWD'] ?? '0',
                    'PWD_No' => Request()->payload['PWD_No'] ?? '',
                    'isActive' => '1',
                    'updatedBy' => Auth()->user()->idnumber,
                ]);
                CustomerGroupMapping::where('customer_id',Request()->payload['id'])->update([
                    'customer_id'=>$customers->id,
                    'group_id'=>Request()->payload['customergroup'] ?? '',
                    'isActive' => '1',
                ]);
            }    
        
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
   
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }

    }

}
