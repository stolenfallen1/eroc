<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\HospitalPlan;
use Illuminate\Http\Request;

class HospitalPlanController extends Controller
{
    public function index()
    {
        try {
            $data = HospitalPlan::query();
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
            $check_if_exist = HospitalPlan::select('description')
                        ->where('description', $request->payload['description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = HospitalPlan::create([
                    'description' => $request->payload['description'],
                    'plan_code' => $request->payload['plan_code'],
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

            $data['data'] = HospitalPlan::where('id', $id)->update([
                           'description' => $request->payload['description'],
                           'plan_code' => $request->payload['plan_code'],
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
            $data['data'] = HospitalPlan::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
