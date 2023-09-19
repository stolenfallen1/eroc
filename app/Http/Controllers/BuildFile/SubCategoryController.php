<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Itemsubcategories;
use App\Helpers\Buildfile\SubCategoryFilter;

class SubCategoryController extends Controller
{
    public function mscAllSubcategory(){
        $data = Itemsubcategories::where('category_id', Request()->category_id)->get();
        return response()->json($data,200);
    }
  public function list()
    {
        return (new SubCategoryFilter())->searchable();
    }
    
    public function store(Request $request)
    {
        if(!Itemsubcategories::select('name')->where('name',$request->payload['name'])->first()){
            $data['subcategory'] = Itemsubcategories::create([
                'name' => $request->payload['name'],
                'category_id' => $request->payload['category_id'],
                'parent_id' => $request->payload['id'] ?? 0,
                'node_level' => $request->payload['id'] ?? 0,
                'isactive' => $request->payload['isactive'],
            ]);
            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        }
        $data['msg'] ='Already Exists!'; 
        return Response()->json($data, 200);

    }

    public function update(Request $request, $id)
    {

        $data['subcategory'] = Itemsubcategories::where('id', $id)->update([
            'name' => $request->payload['name'],
            'category_id' => $request->payload['category_id'],
            'isactive' => $request->payload['isactive'],
        ]);
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }


    
}
