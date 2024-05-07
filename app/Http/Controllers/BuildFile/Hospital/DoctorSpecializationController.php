<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\DoctorSpecialization;
use Illuminate\Support\Facades\DB;

class DoctorSpecializationController extends Controller
{
    
    public function index() {
        try {
            $data = DoctorSpecialization::query();
            if(Request()->keyword) {
                $data->where('specialization_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('isactive', 'desc')->orderBy('id', 'asc');
            $page = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);

        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try {
            DoctorSpecialization::updateOrCreate(
                [
                    'specialization_description' => $request->payload['specialization_description']
                ],
                [
                    'specialization_description' => $request->payload['specialization_description'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? false,
                    'createdby' => Auth()->user()->idnumber,
                    'created_at' => now(),
                ]
            );
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::beginTransaction();
        try {
            DoctorSpecialization::where('id', $id)->update([
                'specialization_description' => $request->payload['specialization_description'] ?? '',
                'isactive' => $request->payload['isactive'] ?? '',
                'updatedby' => Auth()->user()->idnumber,
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        DB::beginTransaction();
        try {
            DoctorSpecialization::where('id', $id)->delete();
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    } 
}
