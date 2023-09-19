<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Nationalities;
use Illuminate\Http\Request;

class NationalitiesController extends Controller
{
    public function list()
    {
        try {
            $data = Nationalities::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function index()
    {
        try {
            $data = Nationalities::query();
            if(Request()->keyword) {
                $data->where('nationality', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function store(Request $request)
    {
        try {
            $check_if_exist = Nationalities::select('nationality')
                        ->where('nationality', $request->payload['nationality'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = Nationalities::create([
                    'nationality' => $request->payload['nationality'],
                    'country' => $request->payload['country'],
                    'nationality_code' => $request->payload['nationality_code'],
                    'iso2' => $request->payload['iso2'],
                    'iso3' => $request->payload['iso3'],
                    'isactive' => $request->payload['isactive'],
                ]);
                $data['msg'] = 'Success';
                return Response()->json($data, 200);
            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data['data'] = Nationalities::where('id', $id)->update([
                          'nationality' => $request->payload['nationality'],
                          'country' => $request->payload['country'],
                          'nationality_code' => $request->payload['nationality_code'],
                          'iso2' => $request->payload['iso2'],
                          'iso3' => $request->payload['iso3'],
                          'isactive' => $request->payload['isactive'],
                       ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function destroy($id)
    {
        try {
            $data['data'] = Nationalities::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
