<?php

namespace App\Http\Controllers\MMIS;

use App\Models\MMIS\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\AuditConsignment;
use App\Models\MMIS\inventory\Delivery;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;

class AuditController extends Controller
{
    public function index(){
        $query = AuditConsignment::query();
        $per_page = Request()->per_page;
        return response()->json($query->paginate($per_page), 200);
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

    public function storeConsignment(Request $request){
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $payload = Request()->payload;
            $audit = AuditConsignment::create([
                'pr_id' => $payload['pr_request_id'],
                'po_id' => $payload['po_id'],
                'delivery_id' => $payload['rr_id'],
                'po_consignment_id' => $payload['id'],
                'audit_by' => Auth::user()->idnumber,
                'remarks' => $payload['remarks'],
            ]);
            PurchaseOrderConsignment::where('id', $payload['id'])->update([
                'isaudit' => 1
            ]);
            // DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["audit" => $audit], 200);
        } catch (\Exception $e) {
            // DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function updateConsignment(Request $request, $id){
        $payload = Request()->payload;
        AuditConsignment::where('id',$id)->update([
            'remarks' => $payload['remarks']
        ]);
        return response()->json(["message" => 'success'], 200);
    }
}
