<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\POS\vwWarehouseItems;

class NewItemsController extends Controller
{
    public function index()
    {
        try {
            $data = vwWarehouseItems::query();
            if(Request()->keyword) {
                $data->where('name', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->where('branch_id',Auth()->user()->branch_id);
            $data->where('warehouse_Id', Auth()->user()->warehouse_id);
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

}
