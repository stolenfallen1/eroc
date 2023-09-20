<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Helpers\SearchFilter\Items;
use App\Models\BuildFile\ItemGroup;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function searchItem(){
        return (new Items)->searchable();
    }

    public function getItemGroup(){
        $warehouse_id = Request()->wh_id ?? Auth::user()->warehouse_id;
        $item_groups = Warehouses::with('itemGroups')->findOrfail($warehouse_id);
        return response()->json(['item_groups' => $item_groups->itemGroups], 200);
    }
}
