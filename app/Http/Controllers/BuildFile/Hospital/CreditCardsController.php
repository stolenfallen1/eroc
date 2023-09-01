<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\CreditCards;
use Illuminate\Http\Request;

class CreditCardsController extends Controller
{
    public function index()
    {
        try {

            $data = CreditCards::query();
            $data->with('bank');
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = CreditCards::select('description')
                        ->where('description', $request->payload['description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = CreditCards::create([
                    'bank_id' => $request->payload['bank_id'],
                    'description' => $request->payload['description'],
                    'payment_method_id' => '2',
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
            $data['data'] = CreditCards::where('id', $id)->update([
                    'bank_id' => $request->payload['bank_id'],
                    'description' => $request->payload['description'],
                    'payment_method_id' => '2',
                    'isactive' => $request->payload['isactive'],
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
        try {
            $data['data'] = CreditCards::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
