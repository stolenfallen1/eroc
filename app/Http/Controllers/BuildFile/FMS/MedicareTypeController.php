<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\MedicareType;

class MedicareTypeController extends Controller
{
     public function list(){
        $data = MedicareType::get();
        return response()->json($data, 200);
     }
    public function index()
    {
        try {
            $data = MedicareType::query();
            if(Request()->keyword) {
                $data->where('medicare_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = MedicareType::select('medicare_description')
                       ->where('medicare_description', $request->payload['medicare_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = MedicareType::create([
                    'isActive' => $request->payload['isActive'],
                    'medicare_description' => $request->payload['medicare_description'],
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

            $data['data'] = MedicareType::where('id', $id)->update([
                          'isActive' => $request->payload['isActive'],
                          'medicare_description' => $request->payload['medicare_description'],
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
        $data['data'] = MedicareType::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
