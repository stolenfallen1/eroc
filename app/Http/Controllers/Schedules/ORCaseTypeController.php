<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedules\ORCaseTypeModel;

class ORCaseTypeController extends Controller
{
    public function index()
    {
        $casetypes = ORCaseTypeModel::query();
        if (Request()->keyword) {
            $casetypes->where('category_name', 'LIKE', '%' . Request()->keyword . '%');
        }
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $casetypes->paginate($per_page);
    }

    public function store(Request $request)
    {
        $casetype = ORCaseTypeModel::where('category_name', $request->payload['category_name'])->first();
        if (!$casetype) {
            // The site doesn't exist, so create a new one
            $casetype = ORCaseTypeModel::create([
                'category_name' => $request->payload['category_name'],
                'isactive' => $request->payload['isactive'],
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'category_name created successfully', 'data' => $casetype], 200);
        }

        // If the site already exists, return an error response
        return response()->json(['message' => 'category_name Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $casetype = ORCaseTypeModel::find($id);
        $casetype->update([
            'category_name' => $request->payload['category_name'],
            'isactive' => $request->payload['isactive'],
        ]);
        return $casetype;
    }

    public function destroy($id)
    {
        $casetype = ORCaseTypeModel::find($id);
        $casetype->delete();
        return $casetype;
    }
}
