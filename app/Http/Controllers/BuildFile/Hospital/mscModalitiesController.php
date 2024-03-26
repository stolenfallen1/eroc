<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class mscModalitiesController extends Controller
{
    public function list()
    {
        $data = mscHospitalServicesSection::where('fms_transaction_id',Request()->revenue_id)->get();
        return response()->json($data, 200);
    }
    public function index()
    {
        try {
            $data = mscHospitalServicesSection::query();

            if(Request()->keyword) {
                $data->where('section_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
