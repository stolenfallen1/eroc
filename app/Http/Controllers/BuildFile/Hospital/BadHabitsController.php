<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\BadHabits;
use Illuminate\Http\Request;

class BadHabitsController extends Controller
{

    public function index() {
        try {
            $perPage = request()->query('per_page', 10);
            $data = BadHabits::where('isactive', 1)->paginate($perPage);
    
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    
    
    public function search() {

    }

    public function store(Request $request) {
        try {
            $check_if_exist = BadHabits::where('description', $request->input('description'))->first();
    
            if (!$check_if_exist) {
                $newBadHabit = new BadHabits();
                $newBadHabit->description = $request->input('description');
                $newBadHabit->desc_remarks = $request->input('desc_remarks');
                $newBadHabit->isactive = true; 
                $newBadHabit->createdBy = Auth()->user()->idnumber;
                $newBadHabit->created_at = now(); 
                $newBadHabit->save();
    
                $data['msg'] = 'Success';
                return response()->json($data, 200);
            } else {
                $data['msg'] = 'Already Exists!';
                return response()->json($data, 200);
            }
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    

    public function update(Request $request, $id) {
        try {
            // return $request->all();
            $badHabit = BadHabits::find($id);
            // return $badHabit;
            if ($badHabit) {
                $badHabit->description = $request->input('description');
                $badHabit->desc_remarks = $request->input('desc_remarks');
                $badHabit->updatedBy = Auth()->user()->idnumber;
                $badHabit->updated_at = now();
                $badHabit->save();
    
                $data['msg'] = 'Success';
                return response()->json($data, 200);
            } else {
                $data['msg'] = 'Bad Habit not found!';
                return response()->json($data, 200);
            }
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        } 
    }
}
