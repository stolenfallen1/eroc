<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\CostCenter;

class CostCenterController extends Controller
{
    public function index()
    {
        try {

            $data = CostCenter::query();
            $data->with('department');
            if(Request()->keyword) {
                $data->where('costcenter_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = CostCenter::select('costcenter_description')
                       ->where('costcenter_description', $request->payload['costcenter_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = CostCenter::create([
                    'department_id' => $request->payload['department_id'],
                    'costcenter_description' => $request->payload['costcenter_description'],
                    'createdBy' => Auth()->user()->idnumber,
                ]);
                $data['msg'] = 'Success';
                return Response()->json($data, 200);
            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);

        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {

        try {

            $data['data'] = CostCenter::where('id', $id)->update([
                          'department_id' => $request->payload['department_id'],
                          'costcenter_description' => $request->payload['costcenter_description'],
                          'createdBy' => Auth()->user()->idnumber,
                       ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        } catch (Exception $e) {
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
        $data['data'] = CostCenter::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
