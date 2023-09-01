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
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json(['warehouse' => $data->paginate($page)], 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function branch_warehouse(Request $request)
    {
        return response()->json(['data' => Warehouses::where('warehouse_Branch_Id', Request()->branch_id)->get()]);
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
                    'isWarehouse' => $request->payload['isWarehouse'],
                    'isDispensing' => $request->payload['isDispensing'],
                    'isPurchasing' => $request->payload['isPurchasing'],
                    'isAllowedRR' => $request->payload['isAllowedRR'],
                    'warehouse_Code' => $request->payload['warehouse_Code'],
                    'warehouse_description' => $request->payload['warehouse_description'],
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
            $data['warehouse'] = Warehouses::where('id', $id)->update([
                                   'warehouse_Group_Id' => $request->payload['warehouse_Group_Id'],
                                   'warehouse_Branch_Id' => $request->payload['warehouse_Branch_Id'],
                                   'isWarehouse' => $request->payload['isWarehouse'] ?? false,
                                   'isDispensing' => $request->payload['isDispensing'] ?? false,
                                   'isPurchasing' => $request->payload['isPurchasing'] ?? false,
                                   'isAllowedRR' => $request->payload['isAllowedRR'] ?? false,
                                   'warehouse_Code' => $request->payload['warehouse_Code'],
                                   'warehouse_description' => $request->payload['warehouse_description'],
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
            $data['data'] = Warehouses::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
