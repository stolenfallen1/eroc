<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function list() {
        try {
            $data = PaymentMethod::where('isActive', 1)->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function index()
    {
        try {
            $data = PaymentMethod::query();
            if(Request()->keyword) {
                $data->where('payment_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = PaymentMethod::select('payment_description')
                        ->where('payment_description', $request->payload['payment_description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = PaymentMethod::create([
                    'payment_description' => $request->payload['payment_description'],
                    'isActive' => $request->payload['isActive'],
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
            $data['data'] = PaymentMethod::where('id', $id)->update([
                          'payment_description' => $request->payload['payment_description'],
                           'isActive' => $request->payload['isActive'],
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
            $data['data'] = PaymentMethod::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
