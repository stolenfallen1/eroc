<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\DosageForm;
use App\Models\BuildFile\Drugadministration;
use Illuminate\Http\Request;

class DosageFormController extends Controller
{
    public function index()
    {
        try {
            $data = DosageForm::query();
            if(Request()->keyword) {
                $data->where('form_name', 'LIKE', '%'.Request()->keyword.'%');
                $data->OrWhere('form_code', 'LIKE', '%'.Request()->keyword.'%');

            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }
    public function list()
    {
        return response()->json(['dosage_forms' => DosageForm::all()], 200);
    }
    public function store(Request $request)
    {
        try {
            $check_if_exist = DosageForm::select('form_name')->where('form_name', $request->payload['form_name'])->first();
            if(!$check_if_exist) {
                $data['data'] = DosageForm::create([
                    'form_name' => $request->payload['form_name'],
                    'form_code' => $request->payload['form_code'],
                    'isActive' => $request->payload['isActive'],
                ]);
                $data['msg'] = 'Record successfully saved';
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
          
            $data['data'] = DosageForm::where('id',$id)->update([
                'form_name' => $request->payload['form_name'],
                'form_code' => $request->payload['form_code'],
                'isActive' => $request->payload['isActive'],
            ]);
            $data['msg'] = 'Record successfully update';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
     public function destroy($id)
    {
        try {
            $data['data'] = DosageForm::where('id', $id)->delete();
            $data['msg'] = 'Record successfully deleted';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
