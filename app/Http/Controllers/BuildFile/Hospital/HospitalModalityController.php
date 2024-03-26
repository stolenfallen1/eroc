<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalModalities;

class HospitalModalityController extends Controller
{
    public function list()
    {
        $data = mscHospitalModalities::get();
        return response()->json($data, 200);
    }
    public function index()
    {
        try {
            $data = mscHospitalModalities::query();
            if(Request()->keyword) {
                $data->where('modality', 'LIKE', '%'.Request()->keyword.'%');
                $data->orWhere('modilaty_description', 'LIKE', '%'.Request()->keyword.'%');
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
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            if(!mscHospitalModalities::where('modality', $payload["modality"])->exists()) {
                $data = mscHospitalModalities::create([
                    'modality' => $payload['modality'],
                    'modilaty_description' => $payload['modilaty_description'],
                    'isActive' => $payload['isActive'] ? 1 : 0,
                ]);
            }
            DB::connection('sqlsrv')->commit();
            return response()->json(['msg' => 'Record successfully saved'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            $data = mscHospitalModalities::where('id',$id)->update([
                'modality' => $payload['modality'],
                'modilaty_description' => $payload['modilaty_description'],
                'isActive' => $payload['isActive'] ? 1 : 0,
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(['msg' => 'Record successfully updated'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

     public function destroy($id)
    {
        try {
            $data['data'] = mscHospitalModalities::where('id', $id)->delete();
            $data['msg'] = 'Record Successfully Deleted';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
