<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Helpers\SearchFilter\Procurements\PurchaseOrders;

class PurchaseOrderController extends Controller
{
    public function index() {
        return (new PurchaseOrders)->searchable();
    }

    public function show($id)
    {
        return purchaseOrderMaster::with(['details'=>function($q){
            $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas');
        }, 'purchaseRequest' => function($q){
            $q->with('user', 'itemGroup', 'category');
        }, 'vendor', 'warehouse', 'user'])->findOrfail($id);
    }

    public function getByNumber()
    {
        return purchaseOrderMaster::with(['details'=>function($q){
            $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas');
        }, 'purchaseRequest' => function($q){
            $q->with('user', 'itemGroup', 'category');
        }, 'vendor', 'warehouse', 'user'])
        ->where(function($q){
            $q->where(function($q1){
                $q1->where('po_Document_total_net_amount', '<', 100000)->where('corp_admin_approved_by', '!=', NULL);
            })->orWhere(function($q2){
                $q2->where('po_Document_total_net_amount', '>', 99999)->where('ysl_approved_by', '!=', NULL);
            });
        })
        ->whereRaw("CONCAT(po_Document_prefix,'',po_Document_number,'',po_Document_suffix) = ?", Request()->number )
        ->first();
    }

    public function store(Request $request) {

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $uom = Unitofmeasurement::where('name', 'like', '%Days')->first();
            foreach ($request->purchase_orders as $purchase_order) {
                $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PO1'])->first();
                $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
                $prefix = $sequence->seq_prefix;
                $suffix = $sequence->seq_suffix;
                $po = purchaseOrderMaster::create([
                    'po_Document_number' => $number,
                    'po_Document_prefix' => $prefix,
                    'po_Document_suffix' => $suffix,
                    'po_Document_branch_id' => (int)$purchase_order['po_Document_branch_id'],
                    'po_Document_warehouse_group_id' => (int)$purchase_order['po_Document_warehouse_group_id'],
                    'po_Document_warehouse_id' =>  (int)$purchase_order['po_Document_warehouse_id'],
                    'po_Document_transaction_date' => Carbon::now(),
                    'po_Document_vendor_id' => (int)$purchase_order['po_Document_vendor_id'],
                    'po_Document_terms_id' => (int)$purchase_order['po_Document_terms_id'],
                    'po_Document_currency_id' => (int)$purchase_order['po_Document_currency_id'],
                    'po_Document_expected_deliverydate' => Carbon::now()->addDays($purchase_order['lead_time']),
                    'po_Document_due_date_unit' => (int)$uom->id,
                    'po_Document_due_date_value' =>(int)$purchase_order['lead_time'],
                    'po_Document_overdue_date_value' => 0,
                    'po_Document_total_item_ordered' => sizeof($purchase_order['items']),
                    'po_Document_total_gross_amount' => $purchase_order['po_Document_total_gross_amount'],
                    'po_Document_discount_percent' =>  $purchase_order['po_Document_discount_percent'],
                    'po_Document_discount_amount' => $purchase_order['po_Document_discount_amount'],
                    'po_Document_isvat_inclusive' => $purchase_order['po_Document_isvat_inclusive'],
                    'po_Document_vat_percent' => $purchase_order['po_Document_vat_percent'],
                    'po_Document_vat_amount' => $purchase_order['po_Document_vat_amount'],
                    'po_Document_total_net_amount' => $purchase_order['po_Document_total_net_amount'],
                    'pr_request_id' => $purchase_order['pr_request_id'],
                    'po_Document_userid' => $authUser->id,
                    'po_status_id' => 1,
    
                ]);
                
                $sequence->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
                ]);
                
                foreach ($purchase_order['items'] as $item) {
                    $po->details()->create([
                        'po_Detail_item_id' => $item['item_Id'],
                        'po_Detail_item_listcost' => $item['recommended_canvas']['canvas_item_net_amount'],
                        'po_Detail_item_qty' => $item['recommended_canvas']['canvas_Item_Qty'],
                        'po_Detail_item_unitofmeasurement_id' => $item['recommended_canvas']['canvas_Item_UnitofMeasurement_Id'],
                        'po_Detail_item_discount_percent' => $item['recommended_canvas']['canvas_item_discount_percent'],
                        'po_Detail_item_discount_amount' => $item['recommended_canvas']['canvas_item_discount_amount'],
                        'po_Detail_vat_percent' => $item['recommended_canvas']['canvas_item_vat_rate'],
                        'po_Detail_vat_amount' => $item['recommended_canvas']['canvas_item_vat_amount'],
                        'po_Detail_net_amount' => round($item['recommended_canvas']['canvas_item_net_amount'], 4),
                        'pr_detail_id' => $item['id'],
                        'canvas_id' => $item['recommended_canvas']['id'],
                        'isFreeGoods' => $item['recommended_canvas']['isFreeGoods'],
                    ]);
                }
            }
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
    }

    public function approve(Request $request){
        $user = auth()->user();
        if($user->role->name == 'comptroller'){
            $this->approveByComptroller($request);
        }
        if($user->role->name == 'administrator'){
            $this->approvedByAdmin($request);
        }
        if($user->role->name == 'corporate admin'){
            $this->approvedByCorpAdmin($request);
        }
        if($user->role->name == 'president'){
            $this->approvedByPresident($request);
        }
        return response()->json(['message' => 'success'], 200);
    }

    private function approveByComptroller($request)
    {
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            if($detail['isapproved'] == true){
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'comptroller_approved_by' => auth()->user()->id,
                    'comptroller_approved_date' => Carbon::now()
                ]);
                $isdecline = false;
            }else{
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'comptroller_cancelled_by' => auth()->user()->id,
                    'comptroller_cancelled_date' => Carbon::now(),
                    'comptroller_cancelled_remarks' => $request->remarks
                ]);
            }
        }
        if($isdecline){
            purchaseOrderMaster::where('id', $request['id'])->update([
                'comptroller_cancelled_by' => auth()->user()->id,
                'comptroller_cancelled_date' => Carbon::now(),
                'comptroller_cancelled_remarks' =>  $request->remarks
            ]);
        }else{
            purchaseOrderMaster::where('id', $request['id'])->update([
                'comptroller_approved_by' => auth()->user()->id,
                'comptroller_approved_date' => Carbon::now(),
            ]);
        }
    }

    private function approvedByAdmin($request){
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            if($detail['isapproved'] == true){
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'admin_approved_by' => auth()->user()->id,
                    'admin_approved_date' => Carbon::now()
                ]);
                $isdecline = false;
            }else{
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'admin_cancelled_by' => auth()->user()->id,
                    'admin_cancelled_date' => Carbon::now(),
                    'admin_cancelled_remarks' => $request->remarks
                ]);
            }
        }
        if($isdecline){
            purchaseOrderMaster::where('id', $request['id'])->update([
                'admin_cancelled_by' => auth()->user()->id,
                'admin_cancelled_date' => Carbon::now(),
                'admin_cancelled_remarks' =>  $request->remarks
            ]);
        }else{
            purchaseOrderMaster::where('id', $request['id'])->update([
                'admin_approved_by' => auth()->user()->id,
                'admin_approved_date' => Carbon::now(),
            ]);
        }
    }

    private function approvedByCorpAdmin($request){
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            if($detail['isapproved'] == true){
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'corp_admin_approved_by' => auth()->user()->id,
                    'corp_admin_approved_date' => Carbon::now()
                ]);
                $isdecline = false;
            }else{
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'corp_admin_cancelled_by' => auth()->user()->id,
                    'corp_admin_cancelled_date' => Carbon::now(),
                    'corp_admin_cancelled_remarks' => $request->remarks
                ]);
            }
        }
        if($isdecline){
            purchaseOrderMaster::where('id', $request['id'])->update([
                'corp_admin_cancelled_by' => auth()->user()->id,
                'corp_admin_cancelled_date' => Carbon::now(),
                'corp_admin_cancelled_remarks' =>  $request->remarks
            ]);
        }else{
            purchaseOrderMaster::where('id', $request['id'])->update([
                'corp_admin_approved_by' => auth()->user()->id,
                'corp_admin_approved_date' => Carbon::now(),
            ]);
        }
    }

    private function approvedByPresident($request){
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            if($detail['isapproved'] == true){
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'ysl_approved_by' => auth()->user()->id,
                    'ysl_approved_date' => Carbon::now()
                ]);
                $isdecline = false;
            }else{
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'ysl_cancelled_by' => auth()->user()->id,
                    'ysl_cancelled_date' => Carbon::now(),
                    'ysl_cancelled_remarks' => $request->remarks
                ]);
            }
        }
        if($isdecline){
            purchaseOrderMaster::where('id', $request['id'])->update([
                'ysl_cancelled_by' => auth()->user()->id,
                'ysl_cancelled_date' => Carbon::now(),
                'ysl_cancelled_remarks' =>  $request->remarks
            ]);
        }else{
            purchaseOrderMaster::where('id', $request['id'])->update([
                'ysl_approved_by' => auth()->user()->id,
                'ysl_approved_date' => Carbon::now(),
            ]);
        }
    }

    public function destroy() {

    }
}
