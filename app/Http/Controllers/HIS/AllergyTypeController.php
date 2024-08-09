<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\AllergyType;
use Illuminate\Http\Request;

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
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
