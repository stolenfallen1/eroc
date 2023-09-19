<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{

     public function province()
    {
        return response()->json(['data' => Province::where('region_code',Request()->region_code)->get()], 200);
    }

    public function index()
    {
        $data = Province::query();
        $data->with('regions');
        if(Request()->keyword) {
            $data->where('province_name', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Province::select('province_name', 'province_code')->where('province_code', $request->payload['province_code'])->where('province_name', $request->payload['province_name'])->first();
        if(!$check_if_exist) {
            $data['data'] = Province::create([
                'province_name' => $request->payload['province_name'],
                'province_code' => (int)$request->payload['province_code'],
                'region_code' => (int)$request->payload['region_code'],
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
        $data['data'] = Province::where('id', $id)->update([
                'province_name' => $request->payload['province_name'],
                'province_code' => (int)$request->payload['province_code'],
                'region_code' => (int)$request->payload['region_code'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
