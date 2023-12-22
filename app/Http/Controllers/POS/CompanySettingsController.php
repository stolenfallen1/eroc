<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\POSSetting;

class CompanySettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $data = POSSetting::query();
            if(Request()->keyword) {
                $data->where('company_name', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
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
        DB::connection('sqlsrv')->beginTransaction();
        try{
            POSSetting::create([
                'company_name'=>$request->payload['company_name'] ?? '',
                'company_address_bldg'=>$request->payload['company_address_bldg'] ?? '',
                'company_address_streetno'=>$request->payload['company_address_streetno'] ?? '',
                'company_address_region'=>$request->payload['company_address_region'] ?? '',
                'company_address_municipality'=>$request->payload['company_address_municipality'] ?? '',
                'company_address_barangay'=>$request->payload['company_address_barangay'] ?? '',
                'company_address_zipcode'=>$request->payload['company_address_zipcode'] ?? '',
                'company_address_country'=>$request->payload['company_address_country'] ?? '',
                'company_address_email'=>$request->payload['company_address_email'] ?? '',
                'company_tin'=>$request->payload['company_tin'] ?? '',
                'vat_rate'=>$request->payload['vat_rate'] ?? '',
                'seniorcitizen_discount_rate'=>$request->payload['seniorcitizen_discount_rate'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
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
        DB::connection('sqlsrv')->beginTransaction();
        try{
            POSSetting::where('id',$id)->update([
                'company_name'=>$request->payload['company_name'] ?? '',
                'company_address_bldg'=>$request->payload['company_address_bldg'] ?? '',
                'company_address_streetno'=>$request->payload['company_address_streetno'] ?? '',
                'company_address_region'=>$request->payload['company_address_region'] ?? '',
                'company_address_municipality'=>$request->payload['company_address_municipality'] ?? '',
                'company_address_barangay'=>$request->payload['company_address_barangay'] ?? '',
                'company_address_zipcode'=>$request->payload['company_address_zipcode'] ?? '',
                'company_address_country'=>$request->payload['company_address_country'] ?? '',
                'company_address_email'=>$request->payload['company_address_email'] ?? '',
                'company_tin'=>$request->payload['company_tin'] ?? '',
                'vat_rate'=>$request->payload['vat_rate'] ?? '',
                'seniorcitizen_discount_rate'=>$request->payload['seniorcitizen_discount_rate'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
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
        $details = POSSetting::find($id);
        $details->delete();
       return response()->json(["message" =>  'Record successfully deleted','status' => '200'], 200);

    }
}
