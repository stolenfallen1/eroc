<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Zipcode;
use Illuminate\Http\Request;

class ZipcodeController extends Controller
{
    public function index()
    {
        $data = Zipcode::query();
        $data->with('regions', 'provinces', 'muncipalities');
        if(Request()->keyword) {
            $data->where('zip_code', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Zipcode::select('zip_code')->where('zip_code', $request->payload['zip_code'])->first();
        if(!$check_if_exist) {
            $data['data'] = Zipcode::create([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_code' => (int)$request->payload['municipality_code'],
                'zip_code' => $request->payload['zip_code'],
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
        $data['data'] = Zipcode::where('id', $id)->update([
                'region_code' => (int)$request->payload['region_code'],
                'province_code' => (int)$request->payload['province_code'],
                'municipality_code' => (int)$request->payload['municipality_code'],
                'zip_code' => $request->payload['zip_code'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
