<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
   public function list()
    {
        try {
            $data = Company::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function index() {
        try {
            $data = Company::query();
            if(Request()->keyword) {
                $data->where('guarantor_name', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'asc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
