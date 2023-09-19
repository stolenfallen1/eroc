<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Itemsubcategories;

class ClassificationController extends Controller
{
    public function classification(Request $request)
    {
        $data['subcategory'] = Itemsubcategories::where('parent_id', $request->sub_category_id)->get();
        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        try {
            if(!Itemsubcategories::select('name')->where('name', $request->payload['name'])->first()) {
                $subcategory = Itemsubcategories::create([
                    'name' => $request->payload['name'],
                    'category_id' => $request->payload['category_id'],
                    'parent_id' => $request->payload['id'],
                    'isactive' => $request->payload['isactive'],
                ]);
                $subcategory->update_node();
                $subcategory->save();
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
            $data['subcategory'] = Itemsubcategories::where('id', $id)->update([
                       'name' => $request->payload['name'],
                       'category_id' => $request->payload['category_id'],
                       'parent_id' => $request->payload['id'],
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
            $data['data'] = Itemsubcategories::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}

