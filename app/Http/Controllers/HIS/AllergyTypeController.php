<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\AllergySymptoms;
use App\Models\HIS\AllergyType;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllergyTypeController extends Controller
{
    //
    public function index() 
    {
        try {
            $data = AllergyType::query();
            if(Request()->keyword) {
                $data->where('allergy_name', 'LIKE', '%'.Request()->keyword.'%');
            } 
            $data->where('isactive', 1)->orderBy('id', 'asc');
            $page  = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);
    
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }
    public function store(Request $request) 
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $allergy = AllergyType::where('allergy_name', $request->payload['allergy_name'])->first();
            if ($allergy) {
                throw new \Exception('Allergy Type already exists');
            } else {
                AllergyType::create([
                    'allergy_name' => ucwords($request->payload['allergy_name']),
                    'allergy_code' => null,
                    'allergy_description' => $request->payload['allergy_description'] ?? null,
                    'isactive' => 1,
                    'created_at' => Carbon::now(),
                    'createdby' => Auth()->user()->idnumber,
                ]);

                DB::connection('sqlsrv')->commit();
                return response()->json(['message' => 'Allergy Type successfully created'], 200);
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) 
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $allergy = AllergyType::findOrFail($id);

            if ($allergy->allergy_name == $request->payload['allergy_name']) {
                throw new \Exception('Allergy Type already exists');
            } else {
                $allergy->update([
                    'allergy_name' => ucwords($request->payload['allergy_name']) ?? $allergy->allergy_name,
                    'allergy_code' => $request->payload['allergy_code'] ?? $allergy->allergy_code,
                    'allergy_description' => $request->payload['allergy_description'] ?? $allergy->allergy_description,
                    'isactive' => 1,
                    'updated_at' => Carbon::now(),
                    'updatedby' => Auth()->user()->idnumber,
                ]);

                DB::connection('sqlsrv')->commit();
                return response()->json(['message' => 'Allergy Type successfully updated'], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

    public function archive($id) 
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $allergy = AllergyType::findOrFail($id);
            if ($allergy) {
                $allergy->update([
                    'isactive' => 0,
                    'updated_at' => Carbon::now(),
                    'updatedby' => Auth()->user()->idnumber,
                ]);
                DB::connection('sqlsrv')->commit();
                return response()->json(['message' => 'Allergy Type successfully archived'], 200);
            } else {
                throw new \Exception('Allergy Type not found');
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

    public function getAllergySymptoms() 
    {
        try {
            $data = AllergySymptoms::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }
}
