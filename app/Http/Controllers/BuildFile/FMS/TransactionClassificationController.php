<?php

namespace App\Http\Controllers\BuildFile\FMS;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionClassification;
use Illuminate\Http\Request;

class TransactionClassificationController extends Controller
{
    public function index()
    {
        try {
            $data = TransactionClassification::query();
            if(Request()->keyword) {
                $data->where('classification_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = TransactionClassification::select('classification_description')
                       ->where('classification_description', $request->payload['classification_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = TransactionClassification::create([
                    'classification_code' => $request->payload['classification_code'],
                    'classification_description' => $request->payload['classification_description'],
                    'isActive' => $request->payload['isActive'],
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
            $data['data'] = TransactionClassification::where('id', $id)->update([
                    'classification_code' => $request->payload['classification_code'],
                    'classification_description' => $request->payload['classification_description'],
                    'isActive' => $request->payload['isActive'],
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
        $data['data'] = TransactionClassification::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
