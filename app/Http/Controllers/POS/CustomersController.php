<?php

namespace App\Http\Controllers\POS;

use App\Helpers\PosSearchFilter\Customer;
use App\Http\Controllers\Controller;
use App\Models\POS\Customers;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = (new Customer())->searchable();
        return response()->json(["data"=>$data,"message" => "success"], 200);
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
                'createdBy' => Auth()->user()->id,
            ]);
        } elseif (Request()->payload['type'] == 'update') {
            $customers = Customers::where('id', Request()->payload['id'])->update([
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
                'updatedBy' => Auth()->user()->id,
            ]);
        }

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\POS\Customers  $customers
     * @return \Illuminate\Http\Response
     */
    public function show(Customers $customers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\POS\Customers  $customers
     * @return \Illuminate\Http\Response
     */
    public function edit(Customers $customers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\POS\Customers  $customers
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customers $customers)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\POS\Customers  $customers
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customers $customers)
    {
        //
    }
}
