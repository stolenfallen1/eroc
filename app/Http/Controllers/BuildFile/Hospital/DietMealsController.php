<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\DietMeals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DietMealsController extends Controller
{
    public function index() {
        try {
            $data = DietMeals::query();
            $data->with('dietTypes','dietSubTypes');
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('isactive', 'desc')->orderBy('id', 'asc');
            $page = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try { 
            DietMeals::updateOrCreate(
                [
                    'meal_description' => $request->payload['meal_description']
                ],
                [
                    'meal_description' => $request->payload['meal_description'] ?? '',
                    'diet_type_id' => $request->payload['diet_type_id'] ?? '',
                    'diet_subtype_id' => $request->payload['diet_subtype_id'] ?? '',
                    'meal_remarks' => $request->payload['meal_remarks'] ?? '',
                    'meal_cost' => $request->payload['meal_cost'] ?? 0.0,
                    'isactive' => $request->payload['isactive'] ?? false,
                    'createdby' => Auth()->user()->idnumber,
                    'created_at' => now(),
                ],
            );
            DB::commit();
            return response()->json(['msg' => 'success'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::beginTransaction();
        try {
            DietMeals::where('id', $id)->update([
                'meal_description' => $request->payload['meal_description'] ?? '',
                'diet_type_id' => $request->payload['diet_type_id'] ?? '',
                'diet_subtype_id' => $request->payload['diet_subtype_id'] ?? '',
                'meal_remarks' => $request->payload['meal_remarks'] ?? '',
                'meal_cost' => $request->payload['meal_cost'] ?? 0.0,
                'isactive' => $request->payload['isactive'] ?? false,
                'updatedby' => Auth()->user()->idnumber,
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json(['msg' => 'success'], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        DB::beginTransaction();
        try {
            DietMeals::where('id', $id)->delete();
            DB::commit();
            return response()->json(['msg' => 'success'], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
