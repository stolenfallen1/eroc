<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use App\Models\POS\POSBIRSettings;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

class BIRSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $data = POSBIRSettings::query();

            if(Request()->keyword) {
                $data->where('pos_supplier_company_name', 'LIKE', '%' . Request()->keyword . '%');
                $data->orWhere('pos_supplier_address_bldg', 'LIKE', '%' . Request()->keyword . '%');
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
        try {
            POSBIRSettings::create([
                'pos_supplier_company_name' => $request->payload['pos_supplier_company_name'] ?? '',
                'pos_supplier_address_bldg' => $request->payload['pos_supplier_address_bldg'] ?? '',
                'pos_supplier_address_streetno' => $request->payload['pos_supplier_address_streetno'] ?? '',
                'pos_supplier_address_region' => $request->payload['pos_supplier_address_region'] ?? '',
                'pos_supplier_address_municipality' => $request->payload['pos_supplier_address_municipality'] ?? '',
                'pos_supplier_address_barangay' => $request->payload['pos_supplier_address_barangay'] ?? '',
                'pos_supplier_address_country' => $request->payload['pos_supplier_address_country'] ?? '',
                'pos_supplier_address_zipcode' => $request->payload['pos_supplier_address_zipcode'] ?? '',
                'pos_supplier_tin' => $request->payload['pos_supplier_tin'] ?? '',
                'bir_accreditation_number' => $request->payload['bir_accreditation_number'] ?? '',
                'bir_accreditation_date' => $request->payload['bir_accreditation_date'] ?? '',
                'bir_accreditation_valid_until_date' => $request->payload['bir_accreditation_valid_until_date'] ?? '',
                'bir_permit_to_use_number' => $request->payload['bir_permit_to_use_number'] ?? '',
                'bir_permit_to_use_issued_date' => $request->payload['bir_permit_to_use_issued_date'] ?? '',
                'isactive' => $request->payload['isactive'] ?? '',
                'CreatedBy' => Auth()->user()->idnumber,
                'created_at' => Carbon::now(),
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

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
        try {
            POSBIRSettings::where('id', $id)->update([
                'pos_supplier_company_name' => $request->payload['pos_supplier_company_name'] ?? '',
                'pos_supplier_address_bldg' => $request->payload['pos_supplier_address_bldg'] ?? '',
                'pos_supplier_address_streetno' => $request->payload['pos_supplier_address_streetno'] ?? '',
                'pos_supplier_address_region' => $request->payload['pos_supplier_address_region'] ?? '',
                'pos_supplier_address_municipality' => $request->payload['pos_supplier_address_municipality'] ?? '',
                'pos_supplier_address_barangay' => $request->payload['pos_supplier_address_barangay'] ?? '',
                'pos_supplier_address_country' => $request->payload['pos_supplier_address_country'] ?? '',
                'pos_supplier_address_zipcode' => $request->payload['pos_supplier_address_zipcode'] ?? '',
                'pos_supplier_tin' => $request->payload['pos_supplier_tin'] ?? '',
                'bir_accreditation_number' => $request->payload['bir_accreditation_number'] ?? '',
                'bir_accreditation_date' => $request->payload['bir_accreditation_date'] ?? '',
                'bir_accreditation_valid_until_date' => $request->payload['bir_accreditation_valid_until_date'] ?? '',
                'bir_permit_to_use_number' => $request->payload['bir_permit_to_use_number'] ?? '',
                'bir_permit_to_use_issued_date' => $request->payload['bir_permit_to_use_issued_date'] ?? '',
                'isactive' => $request->payload['isactive'] ?? '',
                'CreatedBy' => Auth()->user()->idnumber,
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

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
       
        $details = POSBIRSettings::find($id);
        $details->delete();
        return response()->json(["message" =>  'Record successfully deleted','status' => '200'], 200);

    }
}
