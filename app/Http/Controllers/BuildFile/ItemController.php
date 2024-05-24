<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Helpers\SearchFilter\Items;
use App\Models\BuildFile\ItemGroup;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SearchFilter\WarehouseLocationItems;
use App\Models\BuildFile\Hospital\mscHospitalServicesItemGroup;

class ItemController extends Controller
{
    public function searchItem()
    {
        return (new Items())->searchable();
    }
    
    public function searchwarehouseItem()
    {
        return (new WarehouseLocationItems())->searchable();
    }
    
    public function getItemGroup()
    {
        $warehouse_id = Request()->wh_id ?? Auth::user()->warehouse_id;
        $warehouse = Warehouses::with('itemGroups')->findOrfail($warehouse_id);
        $item_groups = $warehouse->itemGroups;
        if(Auth::user()->role->name != 'department head' && Auth::user()->role->name != 'staff') {
            $item_groups = ItemGroup::get();
        }
        return response()->json(['item_groups' => $item_groups], 200);
    }

    public function getServicesItemGroup()
    {
        $item_groups = mscHospitalServicesItemGroup::get();
        return response()->json(['item_groups' => $item_groups], 200);
    }


}
