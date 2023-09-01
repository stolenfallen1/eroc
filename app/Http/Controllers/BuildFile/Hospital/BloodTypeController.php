<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\BloodType;
use Illuminate\Http\Request;

class BloodTypeController extends Controller
{
    public function index()
    {
        try {
            $data = BloodType::query();
            if(Request()->keyword) {
                $data->where('blood_type', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = BloodType::select('blood_type')
                        ->where('blood_type', $request->payload['blood_type'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = BloodType::create([
                    'blood_type' => $request->payload['blood_type'],
                    'AntigensOnRedBloodCells' => $request->payload['AntigensOnRedBloodCells'],
                    'AntibodiesInPlasma' => $request->payload['AntibodiesInPlasma'],
                    'RHFactor' => $request->payload['RHFactor'],
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
            $data['data'] = BloodType::where('id', $id)->update([
                           'blood_type' => $request->payload['blood_type'],
                           'AntigensOnRedBloodCells' => $request->payload['AntigensOnRedBloodCells'],
                           'AntibodiesInPlasma' => $request->payload['AntibodiesInPlasma'],
                           'RHFactor' => $request->payload['RHFactor'],
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
            $data['data'] = BloodType::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
