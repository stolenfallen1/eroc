<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\BadHabits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadHabitsController extends Controller
{

    public function index() {
        try {
            $data = BadHabits::query();
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            } 
            $data->orderBy('isactive', 'desc')->orderBy('id', 'asc');
            $page  = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);
    
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try {
            BadHabits::updateOrCreate(
                [
                    'description'=>  $request->payload['description']
                ],
                [
                    'description' => $request->payload['description'] ?? '',
                    'desc_remarks' => $request->payload['desc_remarks'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? false, 
                    'createdBy' => Auth()->user()->idnumber,
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
            BadHabits::where('id',$id)->update([
                'description' => $request->payload['description'] ?? '',
                'desc_remarks' => $request->payload['desc_remarks'] ?? '',
                'isactive' => $request->payload['isactive'] ?? '', 
                'updatedBy' => Auth()->user()->idnumber,
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
            BadHabits::where('id',$id)->delete();
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
