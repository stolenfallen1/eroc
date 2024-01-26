<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use App\Http\Controllers\Controller;
use App\Models\POS\vwWarehouseItems;

class NewItemsController extends Controller
{
    public function index()
    {
        try {
            $setting = POSSettings::where('company_name', 'like', '%Cebu Doctors University Hospital, Inc.%')->first();
            $data = vwWarehouseItems::query();
            if(Request()->keyword) {
                $data->whereNotIn('id', Request()->selecteditem);
                if(Request()->type == 'barcode'){
                    $data->where('item_Barcode', Request()->keyword);
                }else{
                    $data->where('item_name', 'LIKE', '%' . Request()->keyword . '%');
                }
            }
            if(Request()->category) {
                $data->where('item_Category_Id', Request()->category);
            }
            $data->where('branch_id',Auth()->user()->branch_id);
            $data->where('warehouse_Id', Auth()->user()->warehouse_id);
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            $result['items'] = $data->paginate($page);
            $result['bir_settings'] = $setting; 
            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

}
