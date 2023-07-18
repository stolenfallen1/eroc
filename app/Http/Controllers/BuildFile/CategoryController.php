<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Classifications;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\Itemsubcategories;
use App\Models\BuildFile\Supplierterms;
use App\Models\BuildFile\Suppliertypes;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getAllCategory()
    {
        $category = Itemcategories::query();
        if(Request()->invgroup_id){
            $category->where('invgroup_id', Request()->invgroup_id);
        }
        $category->where('isactive', 1);

        return response()->json(['categories' => $category->get()], 200);
    }

    public function getAllSubCategories()
    {
        $sub_category = Itemsubcategories::query();
        if(Request()->category_id){
            $sub_category->where('category_id', Request()->category_id)->get();
        }
        $sub_category->where('isactive', 1);
        return response()->json(['subcategories' => $sub_category->get()], 200);
    }
    
    public function getAllClassifications()
    {
        $classifications = Classifications::where(['subcategory_id' => Request()->sub_category_id, 'isactive' => 1])->get();
        return response()->json(['classifications' => $classifications], 200);
    }

    public function getAllSupplierCategories()
    {
        $categories = Suppliertypes::where('isactive', 1)->get();
        return response()->json(['categories' => $categories], 200);
    }

    public function getAllSupplierTerms()
    {
        $terms = Supplierterms::where('isactive', 1)->get();
        return response()->json(['terms' => $terms], 200);
    }
}
