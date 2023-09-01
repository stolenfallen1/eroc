<?php

namespace App\Http\Controllers\BuildFile\FMS;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\AccountClass;
use Illuminate\Http\Request;

class AccountClassController extends Controller
{
    public function index()
    {

        try {
            $data = AccountClass::query();
            if(Request()->keyword) {
                $data->where('Description', 'LIKE', '%'.Request()->keyword.'%')->orWhere('Class', 'LIKE', '%'.Request()->keyword.'%');
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

            $check_if_exist = AccountClass::select('Description')
                ->where('Description', $request->payload['Description'])
                ->first();
            if(!$check_if_exist) {
                $data['data'] = AccountClass::create([
                    'acct_type' => $request->payload['acct_type'],
                    'Description' => $request->payload['Description'],
                    'Class' => $request->payload['Class'],
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
            $data['data'] = AccountClass::where('id', $id)->update([
                            'acct_type' => $request->payload['acct_type'],
                            'Description' => $request->payload['Description'],
                            'Class' => $request->payload['Class'],
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
        $data['data'] = AccountClass::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
