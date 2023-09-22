<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\AdmissionType;

class AdmissionTypeController extends Controller
{
    public function list()
    {
        try {
            $data = AdmissionType::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function index()
    {
        try {

            $data = AdmissionType::query();
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = AdmissionType::select('description')
                        ->where('description', $request->payload['description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = AdmissionType::create([
                    'description' => $request->payload['description'],
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
            $data['data'] = AdmissionType::where('id', $id)->update([
                           'description' => $request->payload['description'],
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
        $data['data'] = AdmissionType::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
