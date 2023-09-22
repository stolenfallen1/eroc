<?php

namespace App\Http\Controllers\BuildFile\address;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\address\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    
    public function list()
    {
        $data = Country::get();
        return response()->json($data, 200);
    }
     public function index()
    {
        $data = Country::query();
        if(Request()->keyword) {
            $data->where('country_name', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {
        $check_if_exist = Country::select('country_name','countrycode')->where('countrycode', $request->payload['countrycode'])->where('country_name', $request->payload['country_name'])->first();
        if(!$check_if_exist) {
            $data['data'] = Country::create([
                'country_name' => $request->payload['country_name'],
                'countrycode' => (int)$request->payload['countrycode'],
                'countrycode_iso2' => $request->payload['countrycode_iso2'],
                'countrycode_iso3' => $request->payload['countrycode_iso3'],
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
        $data['data'] = Country::where('id', $id)->update([
                'country_name' => $request->payload['country_name'],
                'countrycode' => (int)$request->payload['countrycode'],
                'countrycode_iso2' => $request->payload['countrycode_iso2'],
                'countrycode_iso3' => $request->payload['countrycode_iso3'],
                'isactive' => $request->payload['isactive']
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
