<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Illuminate\Http\Request;
use App\Models\POS\vwCustomers;
use App\Http\Controllers\Controller;

class NewCustomersController extends Controller
{
    public function index()
    {
        try {
            $data = vwCustomers::query();
            if(Request()->keyword) {
                $data->where('name', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

}
