<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Manufacturer;
use Illuminate\Http\Request;

class MscManufacturerController extends Controller
{
    public function list()
    {
        try {
            $data = Manufacturer::query();
            if(Request()->keyword) {
                $data->where('manufacturer_name', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Manufacturer::select('manufacturer_name', 'manufacturer_code')
                       ->where('manufacturer_name', $request->payload['manufacturer_name'])
                       ->where('manufacturer_code', $request->payload['manufacturer_code'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Manufacturer::create([
                    'manufacturer_code' => $request->payload['manufacturer_code'],
                    'manufacturer_name' => $request->payload['manufacturer_name'],
                    'manufacturer_address' => $request->payload['manufacturer_address'],
                    'contact_person_name' => $request->payload['contact_person_name'],
                    'contact_person_phone' => $request->payload['contact_person_phone'],
                    'contact_person_email' => $request->payload['contact_person_email'],
                    'certifications' => $request->payload['certifications'],
                    'isactive' => $request->payload['isactive']
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
            $data['data'] = Manufacturer::where('id', $id)->update([
                          'manufacturer_code' => $request->payload['manufacturer_code'],
                           'manufacturer_name' => $request->payload['manufacturer_name'],
                           'manufacturer_address' => $request->payload['manufacturer_address'],
                           'contact_person_name' => $request->payload['contact_person_name'],
                           'contact_person_phone' => $request->payload['contact_person_phone'],
                           'contact_person_email' => $request->payload['contact_person_email'],
                           'certifications' => $request->payload['certifications'],
                           'isactive' => $request->payload['isactive']
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
            $data['data'] = Manufacturer::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
