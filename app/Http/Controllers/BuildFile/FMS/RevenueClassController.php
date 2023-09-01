<?php

namespace App\Http\Controllers\BuildFile\FMS;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\RevenueClass;
use Illuminate\Http\Request;

class RevenueClassController extends Controller
{
    public function index()
    {
        try {
            $data = RevenueClass::query();
            if(Request()->keyword) {
                $data->where('revenue_class_name', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }

    public function store(Request $request)
    {
        try {
            $check_if_exist = RevenueClass::select('revenue_class_name')
                       ->where('revenue_class_name', $request->payload['revenue_class_name'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = RevenueClass::create([
                    'revenue_class_code' => $request->payload['revenue_class_code'],
                    'revenue_class_name' => $request->payload['revenue_class_name'],
                    'isactive' => $request->payload['isactive'],
                    'createdBy' => Auth()->user()->idnumber,
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
            $data['data'] = RevenueClass::where('id', $id)->update([
                    'revenue_class_code' => $request->payload['revenue_class_code'],
                    'revenue_class_name' => $request->payload['revenue_class_name'],
                    'isactive' => $request->payload['isactive'],
                    'createdBy' => Auth()->user()->idnumber,
               ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);

        }

    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data['data'] = RevenueClass::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
