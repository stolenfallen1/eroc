<?php

namespace App\Http\Controllers\MMIS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\MMIS\Audit;
use App\Models\MMIS\inventory\Delivery;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function index(){
        
    }

    public function store(Request $request){
        // DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $audit = Audit::create([
                'pr_id' => $request['purchase_order']['pr_Request_id'],
                'po_id' => $request['po_id'],
                'delivery_id' => $request['id'],
                'audit_by' => Auth::user()->idnumber,
                'remarks' => $request->remarks,
            ]);
            Delivery::where('id', $request['id'])->update([
                'isaudit' => 1
            ]);
            // DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["audit" => $audit], 200);
        } catch (\Exception $e) {
            // DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function update(Request $request, Audit $audit){
        $audit->update([
            'remarks' => $request->remarks
        ]);
        return response()->json(["message" => 'success'], 200);
    }

    public function destroy($id){

    }
}
