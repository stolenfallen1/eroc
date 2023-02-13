<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Classifications;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\Itemsubcategories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getAllCategory()
    {
        return response()->json(['categories' => Itemcategories::get()], 200);
    }

    public function getAllSubCategories()
    {
        $sub_categories = Itemsubcategories::where(['category_id' => Request()->category_id, 'isactive' => 1])->get();
        return response()->json(['subcategories' => $sub_categories], 200);
    }
    
    public function getAllClassifications()
    {
        $classifications = Classifications::where(['subcategory_id' => Request()->sub_category_id, 'isactive' => 1])->get();
        return response()->json(['classifications' => $classifications], 200);
    }
}
