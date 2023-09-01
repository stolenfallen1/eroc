<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
   
   public function index()
    {
        $data = Region::query();
        if(Request()->keyword) {
            $data->where('region_name', 'LIKE', '%'.Request()->keyword.'%');
        }
        if(Request()->per_page){
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            $result = $data->paginate($page);
        }else{
            $result = $data->get();
        }
       
        return response()->json($result, 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Region::select('region_name','region_code')->where('region_code', $request->payload['region_code'])->where('region_name', $request->payload['region_name'])->first();
        if(!$check_if_exist) {
            $data['data'] = Region::create([
                'region_name' => $request->payload['region_name'],
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
        $data['data'] = Region::where('id', $id)->update([
                'region_name' => $request->payload['region_name'],
                'region_code' => (int)$request->payload['region_code'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
