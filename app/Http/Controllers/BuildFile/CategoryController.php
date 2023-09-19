<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Supplierterms;
use App\Models\BuildFile\Suppliertypes;
use App\Models\BuildFile\Itemcategories;
use App\Helpers\Buildfile\CategoryFilter;
use App\Models\BuildFile\Classifications;
use App\Models\BuildFile\Itemsubcategories;

class CategoryController extends Controller
{
    public function mscAllcategory()
    {
        $data = Itemcategories::where('invgroup_id', Request()->invgroup_id)->get();
        return response()->json($data, 200);
    }
    public function list()
    {
        return (new CategoryFilter())->searchable();
    }

    public function store(Request $request)
    {
        try {
            if(!Itemcategories::select('name', 'invgroup_id')->where('invgroup_id', $request->payload['invgroup_id'])->where('name', $request->payload['name'])->first()) {
                $data['category'] = Itemcategories::create([
                    'name' => $request->payload['name'],
                    'invgroup_id' => $request->payload['invgroup_id'],
                    'isactive' => $request->payload['isactive'],
                ]);
                $data['msg'] = 'Success';
                return Response()->json($data, 200);
            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data['category'] = Itemcategories::where('id', $id)->update([
                               'name' => $request->payload['name'],
                               'invgroup_id' => $request->payload['invgroup_id'],
                               'isactive' => $request->payload['isactive'],
                           ]);
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function destroy($id)
    {
        try {
            $data['data'] = Itemcategories::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function getAllCategory()
    {
        $category = Itemcategories::query();
        if(Request()->invgroup_id) {
            $category->where('invgroup_id', Request()->invgroup_id);
        }
        $category->where('isactive', 1);

        return response()->json(['categories' => $category->get()], 200);
    }

    public function getAllSubCategories()
    {
        $sub_category = Itemsubcategories::query();
        if(Request()->category_id) {
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
