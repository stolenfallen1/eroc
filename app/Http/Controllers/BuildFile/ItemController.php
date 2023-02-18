<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Helpers\SearchFilter\Items;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\ItemGroup;

class ItemController extends Controller
{
    public function searchItem(){
        return (new Items)->searchable();
    }

    public function getItemGroup(){
        return response()->json(['item_groups' => ItemGroup::where('isactive', 1)->get()], 200);
    }
}
