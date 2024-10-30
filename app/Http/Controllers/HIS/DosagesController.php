<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\mscDosages;
use Illuminate\Http\Request;

class DosagesController extends Controller
{
    //
    public function index() 
    {
        try {
            $data = mscDosages::query();
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            } 
            $data->where('isactive', 1)->orderBy('id', 'asc');
            return response()->json($data->get(), 200);
    
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }
}
