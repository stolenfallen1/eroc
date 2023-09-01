<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\DosageForm;
use App\Models\BuildFile\Drugadministration;
use Illuminate\Http\Request;

class DrugAdministrationController extends Controller
{
    public function index()
    {
        return response()->json(['drug_administration' => Drugadministration::all()], 200);
    }

    public function dosageForms()
    {
        return response()->json(['dosage_forms' => DosageForm::all()], 200);
    }

    public function list()
    {
        try {
            $data = Drugadministration::query();
            if(Request()->keyword) {
                $data->where('route_name', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Drugadministration::select('route_name')
                       ->where('route_name', $request->payload['route_name'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Drugadministration::create([
                    'route_name' => $request->payload['route_name'],
                    'route_description' => $request->payload['route_description'],
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
            $data['data'] = Drugadministration::where('id', $id)->update([
                           'route_name' => $request->payload['route_name'],
                           'route_description' => $request->payload['route_description'],
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
            $data['data'] = Drugadministration::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
