<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Warehousegroups;
use App\Models\BuildFile\Warehouses;
use Illuminate\Http\Request;

class MscWarehouseController extends Controller
{
    public function list()
    {
        try {
            $data = Warehouses::query();
            $data->with('branch', 'warehouseGroup');
            if(Request()->keyword) {
                $data->where('warehouse_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            if(isset(Request()->warehouse_Group_Id)) {
                $data->where('warehouse_Group_Id', Request()->warehouse_Group_Id);
            }

            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json(['warehouse' => $data->paginate($page)], 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function warehouselist(){
        return response()->json(['data' =>Warehouses::get()], 200);
    }

    public function branch_warehouse(Request $request)
    {
        return response()->json(['data' => Warehouses::with('branch')->where('warehouse_Branch_Id', Request()->branch_id)->get()]);
    }

    public function branch(Request $request)
    {
        return response()->json(['data' => Branchs::get()]);
    }

    public function warehousegroup(Request $request)
    {
        return response()->json(['data' => Warehousegroups::get()]);
    }

    public function store(Request $request)
    {
        try {
            $check_if_exist = Warehouses::select('warehouse_Group_Id', 'warehouse_Branch_Id', 'warehouse_description', 'warehouse_Code')
                        ->where('warehouse_Group_Id', $request->payload['warehouse_Group_Id'])
                        ->where('warehouse_Branch_Id', $request->payload['warehouse_Branch_Id'])
                        ->where('warehouse_description', $request->payload['warehouse_description'])
                        ->where('warehouse_Code', $request->payload['warehouse_Code'])
                        ->first();

            if(!$check_if_exist) {
                $data['warehouse'] = Warehouses::create([
                    'warehouse_Group_Id' => $request->payload['warehouse_Group_Id'],
                    'warehouse_Branch_Id' => $request->payload['warehouse_Branch_Id'],
                    'isHemodialysis' => isset($request->payload['isHemodialysis']) ? $request->payload['isHemodialysis'] : 0 ,
                    'isPeritoneal' => isset($request->payload['isPeritoneal']) ? $request->payload['isPeritoneal'] : 0 ,
                    'isLINAC' => isset($request->payload['isLINAC']) ? $request->payload['isLINAC'] : 0,
                    'isCOBALT' => isset($request->payload['isCOBALT']) ? $request->payload['isCOBALT'] : 0,
                    'isChemotherapy' => isset($request->payload['isChemotherapy']) ? $request->payload['isChemotherapy'] : 0,
                    'isBrachytherapy' => isset($request->payload['isBrachytherapy']) ? $request->payload['isBrachytherapy'] : 0,
                    'isDebridement' => isset($request->payload['isDebridement']) ? $request->payload['isDebridement'] : 0 ,
                    'isTBDots' => isset($request->payload['isTBDots']) ? $request->payload['isTBDots'] : 0 ,
                    'isPAD' => isset($request->payload['isPAD']) ? $request->payload['isPAD'] : 0 ,
                    'isRadioTherapy' => isset($request->payload['isRadioTherapy']) ? $request->payload['isRadioTherapy'] : 0,
                    'isWarehouse' => isset($request->payload['isWarehouse']) ? $request->payload['isWarehouse'] : 0,
                    'isDispensing' => isset($request->payload['isDispensing']) ? $request->payload['isDispensing'] : 0,
                    'isPurchasing' => isset($request->payload['isPurchasing']) ? $request->payload['isPurchasing'] : 0,
                    'isAllowedRR' => isset($request->payload['isAllowedRR']) ? $request->payload['isAllowedRR'] : 0,
                    'warehouse_Code' => isset($request->payload['warehouse_Code']) ? $request->payload['warehouse_Code'] : null,
                    'warehouse_description' => isset($request->payload['warehouse_description']) ? $request->payload['warehouse_description'] : null,
                    'isActive' => isset($request->payload['isActive']) ? $request->payload['isActive'] : 0
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
            $data['warehouse'] = Warehouses::where('id', $id)->update([
                    'warehouse_Group_Id' => $request->payload['warehouse_Group_Id'],
                    'warehouse_Branch_Id' => $request->payload['warehouse_Branch_Id'],
                    'isHemodialysis' => isset($request->payload['isHemodialysis']) ? $request->payload['isHemodialysis'] : 0 ,
                    'isPeritoneal' => isset($request->payload['isPeritoneal']) ? $request->payload['isPeritoneal'] : 0 ,
                    'isLINAC' => isset($request->payload['isLINAC']) ? $request->payload['isLINAC'] : 0,
                    'isCOBALT' => isset($request->payload['isCOBALT']) ? $request->payload['isCOBALT'] : 0,
                    'isChemotherapy' => isset($request->payload['isChemotherapy']) ? $request->payload['isChemotherapy'] : 0,
                    'isBrachytherapy' => isset($request->payload['isBrachytherapy']) ? $request->payload['isBrachytherapy'] : 0,
                    'isDebridement' => isset($request->payload['isDebridement']) ? $request->payload['isDebridement'] : 0 ,
                    'isTBDots' => isset($request->payload['isTBDots']) ? $request->payload['isTBDots'] : 0 ,
                    'isPAD' => isset($request->payload['isPAD']) ? $request->payload['isPAD'] : 0 ,
                    'isRadioTherapy' => isset($request->payload['isRadioTherapy']) ? $request->payload['isRadioTherapy'] : 0,
                    'isWarehouse' => isset($request->payload['isWarehouse']) ? $request->payload['isWarehouse'] : 0,
                    'isDispensing' => isset($request->payload['isDispensing']) ? $request->payload['isDispensing'] : 0,
                    'isPurchasing' => isset($request->payload['isPurchasing']) ? $request->payload['isPurchasing'] : 0,
                    'isAllowedRR' => isset($request->payload['isAllowedRR']) ? $request->payload['isAllowedRR'] : 0,
                    'warehouse_Code' => isset($request->payload['warehouse_Code']) ? $request->payload['warehouse_Code'] : null,
                    'warehouse_description' => isset($request->payload['warehouse_description']) ? $request->payload['warehouse_description'] : null,
                    'isActive' => isset($request->payload['isActive']) ? $request->payload['isActive'] : 0
                ]);

            $data['msg'] = 'Record successfully updated';
            return Response()->json($data, 200);


        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function destroy($id)
    {
        try {
            $data['data'] = Warehouses::where('id', $id)->delete();
            $data['msg'] = 'Record successfully deleted';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
