<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Municipality;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    public function municipality(){
        return response()->json(['data' => Municipality::where('region_code', Request()->region_code)->where('province_code', Request()->province_code)->get()], 200);
    }
    public function index()
    {
        $data = Municipality::query();
        $data->with('regions','provinces');
        if(Request()->keyword) {
            $data->where('municipality_name', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Municipality::select('municipality_name', 'municipality_code')->where('municipality_code', $request->payload['municipality_code'])->where('municipality_name', $request->payload['municipality_name'])->first();
        if(!$check_if_exist) {
            $data['data'] = Municipality::create([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_name' => $request->payload['municipality_name'],
                'municipality_code' => (int)$request->payload['municipality_code'],
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
        $data['data'] = Municipality::where('id', $id)->update([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_name' => $request->payload['municipality_name'],
                'municipality_code' => (int)$request->payload['municipality_code'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
