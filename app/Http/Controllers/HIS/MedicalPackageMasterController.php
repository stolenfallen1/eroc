<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\MedicalPackageMaster;
use Illuminate\Http\Request;

class MedicalPackageMasterController extends Controller
{
    //
    public function index() 
    {
        try {
            $data = MedicalPackageMaster::query();
            if(Request()->keyword) {
                $data->where('package_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            // $data->where('package_classification_id', 1);
            $data->where('isactive', 1);
            $data->orderBy('package_description', 'asc');
            $page = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get medical packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
