<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\PriceScheme;

class mscPriceController extends Controller
{
    public function list()
    {
        try {
            $data = PriceScheme::with('priceGroups')->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
    public function index()
    {
        try {
            $data = PriceScheme::query();
            $data->with('priceGroups');
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            }
            if(Request()->price_group) {
                $data->where('msc_price_group_id', Request()->price_group);
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $check_if_exist = PriceScheme::select('description')->where('msc_price_group_id', $request->payload['msc_price_group_id'])->where('description', $request->payload['description'])->first();
            if(!$check_if_exist) {
                $data['data'] = PriceScheme::create([
                    'msc_price_group_id' => $request->payload['msc_price_group_id'],
                    'description' => $request->payload['description'],
                    'isactive' => $request->payload['isactive'] ? 1 : 0,
                ]);
                $data['msg'] = 'Record successfully saved';
                $data['status'] = 'succes';

                return Response()->json($data, 200);
            }
            $data['status'] = 'exist';
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 400);
        } catch (\Exception $e) {
            $data['status'] = 'error';
            return response()->json(["msg" => $e->getMessage()], 404);
        }

    }

    public function update(Request $request, $id)
    {
        try {
            $data['data'] = PriceScheme::where('id', $id)->update([
                'msc_price_group_id' => $request->payload['msc_price_group_id'],
                'description' => $request->payload['description'],
                'isactive' => $request->payload['isactive'] ? 1 : 0,
            ]);
            $data['msg'] = 'Record successfully update';
            $data['status'] = 'succes';

            return Response()->json($data, 200);

        } catch (\Exception $e) {
            $data['status'] = 'error';
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
    public function destroy($id)
    {
        try {
            $data['data'] = PriceScheme::where('id', $id)->delete();
            $data['msg'] = 'Record successfully deleted';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 404);
        }
    }
}
