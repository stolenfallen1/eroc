<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Barangay;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    public function list(){
        return response()->json(['data' => Barangay::where('region_code', Request()->region_code)->where('province_code', Request()->province_code)->where('municipality_code', Request()->municipality_code)->get()], 200);
    }
   public function index()
    {
        $data = Barangay::query();
        $data->with('regions','provinces','muncipalities');
        if(Request()->keyword) {
            $data->where('barangay_name', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Barangay::select('barangay_name')->where('barangay_name', $request->payload['barangay_name'])->first();
        if(!$check_if_exist) {
            $data['data'] = Barangay::create([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_code' => (int)$request->payload['municipality_code'],
                'barangay_name' => $request->payload['barangay_name'],
                'isactive' => $request->payload['isactive']
            ]);
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        }
        $data['msg'] = 'Already Exists!';
        return Response()->json($data, 200);

    }

    public function update(Request $request, $id)
    {
        $data['data'] = Barangay::where('id', $id)->update([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_code' => (int)$request->payload['municipality_code'],
                'barangay_name' => $request->payload['barangay_name'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
