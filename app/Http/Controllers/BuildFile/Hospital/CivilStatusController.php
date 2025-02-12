<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\CivilStatus;
use Illuminate\Http\Request;

class CivilStatusController extends Controller
{
    public function list()
    {
        try {
            $data = CivilStatus::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function index()
    {
        try {

            $data = CivilStatus::query();
            if(Request()->keyword) {
                $data->where('civil_status_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = CivilStatus::select('civil_status_description')
                        ->where('civil_status_description', $request->payload['civil_status_description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = CivilStatus::create([
                    'civil_status_description' => $request->payload['civil_status_description'],
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

            $data['data'] = CivilStatus::where('id', $id)->update([
                           'civil_status_description' => $request->payload['civil_status_description'],
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
            $data['data'] = CivilStatus::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
