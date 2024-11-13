<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use Illuminate\Http\Request;
use App\Models\BuildFile\Vendors;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\SystemSequence;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Helpers\SearchFilter\Procurements\Canvases;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class CanvasController extends Controller
{

    protected $model;
    protected $authUser;
    protected $role;

    public function __construct()
    {
        $this->authUser = auth()->user();
        $this->role = new ParentRole();
    }
    public function index()
    {
        return (new Canvases)->searchable();
    }

    public function countForPO()
    {
        $model = PurchaseRequest::query();
        if($this->role->purchaser()){
            $model->whereIn('invgroup_id', Auth()->user()->assigneditemgroup);
        }
        $model->where(function($q1){
            $q1->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
        })->whereHas('purchaseRequestDetails', function($q){
        $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function($q1){
            $q1->where('canvas_Level2_ApprovedBy', '!=', null);
        })->whereDoesntHave('purchaseOrderDetails');
        });
        if(Auth()->user()->role->name == 'dietary' || Auth()->user()->role->name == 'dietary head'){
            $model->where('isPerishable', 1);
        }else{
            $model->where(function($q){
                $q->where('isPerishable', 0)->orWhere('isPerishable', NULL);
            });
        }
        
        $model->where('pr_Document_Number', 'like', "000%");
        if(Auth::user()->branch_id != 1) $model->where('branch_id', Auth::user()->branch_id); 
        return $model->count();
    }

    public function store(Request $request)
    {

        $authUser = Auth::user();
        $pr = PurchaseRequest::findOrfail($request->pr_request_id);
        $itemDetails = Itemmasters::findOrfail($request->canvas_Item_Id);
        if($pr->branch_Id == 1){
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CSN1','branch_id'=> $pr->branch_Id])->first();
        }else{
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CSN7','branch_id'=> $pr->branch_Id])->first();
        }
        
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;
        
        $discount_amount = 0;
        $vat_amount = 0;
        $total_amount = $request->canvas_item_amount * $request->canvas_Item_Qty;
        
        if($request->canvas_discount_percent){
            $discount_amount = $total_amount * ($request->canvas_discount_percent / 100);
        }

        if($request->canvas_item_vat_rate){
            if($itemDetails->isVatable == 1 || $itemDetails->isVatable != null){
                $vat_amount = ($total_amount - $discount_amount) * ($request->canvas_item_vat_rate / 100);
            }
        }
        $canvas_item_total_amount =($total_amount - $discount_amount) + $vat_amount;

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $itemid = isset($request->canvas_Old_Item_Id) ? $request->canvas_Old_Item_Id : $request->canvas_Item_Id;

            $checkcanvas = CanvasMaster::where('pr_request_id',$request->pr_request_id)->where('canvas_Item_Id',$itemid)->where('vendor_id',$request->vendor_id)->first();
            $canvas = CanvasMaster::updateOrCreate(
                [
                    'pr_request_id' => $request->pr_request_id,
                    'canvas_Item_Id' => $itemid,
                    'vendor_id' => $request->vendor_id
                ],
                [
                'canvas_Document_Number' => $number,
                'canvas_Document_Prefix' => $prefix,
                'canvas_Document_Suffix' => $suffix,
                'canvas_Document_CanvassBy' => Auth::user()->idnumber,
                'canvas_Document_Transaction_Date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'requested_date' => Carbon::parse($request->requested_date),
                'canvas_Branch_Id' => $authUser->branch_id,
                'canvas_Warehouse_Group_Id' => $authUser->warehouse->warehouse_Group_Id,
                'canvas_Warehouse_Id' => $pr->warehouse_Id,
                'vendor_id' => $request->vendor_id,
                'pr_request_id' => $request->pr_request_id,
                'pr_request_details_id' => $request->pr_request_details_id,
                'canvas_Item_Id' => $request->canvas_Item_Id,
                'canvas_Item_Qty' => $request->canvas_Item_Qty,
                'canvas_Item_UnitofMeasurement_Id' => $request->canvas_Item_UnitofMeasurement_Id,
                'canvas_item_amount' => $request->canvas_item_amount,
                'canvas_item_total_amount' => $canvas_item_total_amount,
                'canvas_item_discount_percent' => $request->canvas_discount_percent,
                'canvas_item_discount_amount' => $discount_amount,
                'canvas_item_net_amount' => $canvas_item_total_amount,
                'canvas_lead_time' => $request->canvas_lead_time,
                'canvas_remarks' => $request->canvas_remarks,
                'currency_id' => $request->currency_id,
                'canvas_item_vat_rate' => $request->canvas_item_vat_rate,
                'canvas_item_vat_amount' => $vat_amount,
                'isFreeGoods' => $request->isFreeGoods == true ? 1 : 0,
                'isRecommended' => $checkcanvas ? $checkcanvas->isRecommended : 0,
            ]);
    
            if (isset($request->attachments) && $request->attachments != null && sizeof($request->attachments) > 0) {
                foreach($request->attachments as $key => $attachment){
                    $file = storeDocument($attachment, "canvas/attachments", $key);
                    $canvas->attachments()->create([
                        'filepath' => $file[0],
                        'filename' => $file[2]
                    ]);
                }
            }

            if($request->isFreeGoods == true){
                $prDetails = PurchaseRequestDetails::where('pr_request_id',$request->pr_request_id)->first();
                // $prDetails->updateOrCreate(
                //     [
                //         'pr_request_id' => $request->pr_request_id,
                //         'item_Id' => $itemid
                //     ],
                //     [
                //         'item_Id' => $request->canvas_Item_Id,
                //         'item_ListCost' => 0,
                //         'discount' => 0,
                //         'item_Request_Qty' => $request->canvas_Item_Qty,
                //         'prepared_supplier_id' => $request->vendor_id,
                //         'lead_time' => 0,
                //         'vat_rate' => 0,
                //         'vat_type' => 0,
                //         'isFreeGoods'=>1,
                //         'item_Request_UnitofMeasurement_Id' => $request->canvas_Item_UnitofMeasurement_Id,
                //         'item_Request_Department_Approved_Qty' => $request->canvas_Item_Qty,
                //         'item_Request_Department_Approved_UnitofMeasurement_Id' => $prDetails->item_Request_Department_Approved_UnitofMeasurement_Id,
                //         'item_Branch_Level1_Approved_Qty' => $request->canvas_Item_Qty,
                //         'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $prDetails->item_Branch_Level1_Approved_UnitofMeasurement_Id,
                //         'item_Branch_Level2_Approved_Qty' => $request->canvas_Item_Qty,
                //         'item_Branch_Level2_Approved_UnitofMeasurement_Id' => $prDetails->item_Branch_Level2_Approved_UnitofMeasurement_Id,
                //         'item_Branch_Level3_Approved_Qty' => $request->canvas_Item_Qty,
                //         'item_Branch_Level3_Approved_UnitofMeasurement_Id' => $prDetails->item_Branch_Level3_Approved_UnitofMeasurement_Id,
                //         'item_Branch_Level4_Approved_Qty' => $request->canvas_Item_Qty,
                //         'item_Branch_Level4_Approved_UnitofMeasurement_Id' => $prDetails->item_Branch_Level4_Approved_UnitofMeasurement_Id,
                //         'pr_DepartmentHead_ApprovedBy' => $prDetails->pr_DepartmentHead_ApprovedBy,
                //         'pr_DepartmentHead_ApprovedDate' => $prDetails->pr_DepartmentHead_ApprovedDate,
                //         'pr_DepartmentHead_CancelledBy' => $prDetails->pr_DepartmentHead_CancelledBy,
                //         'pr_DepartmentHead_CancelledDate' => $prDetails->pr_DepartmentHead_CancelledDate,
                //         'pr_DepartmentHead_Cancelled_Remarks' => $prDetails->pr_DepartmentHead_Cancelled_Remarks,
                //         'pr_Branch_Level1_ApprovedBy' => $prDetails->pr_Branch_Level1_ApprovedBy,
                //         'pr_Branch_Level1_ApprovedDate' => $prDetails->pr_Branch_Level1_ApprovedDate,
                //         'pr_Branch_Level1_CancelledBy' => $prDetails->pr_Branch_Level1_CancelledBy,
                //         'pr_Branch_Level1_CancelledDate' => $prDetails->pr_Branch_Level1_CancelledDate,
                //         'pr_Branch_Level1_Cancelled_Remarks' => $prDetails->pr_Branch_Level1_Cancelled_Remarks,
                //         'pr_Branch_Level2_ApprovedBy' => $prDetails->pr_Branch_Level2_ApprovedBy,
                //         'pr_Branch_Level2_ApprovedDate' => $prDetails->pr_Branch_Level2_ApprovedDate,
                //         'pr_Branch_Level2_CancelledBy' => $prDetails->pr_Branch_Level2_CancelledBy,
                //         'pr_Branch_Level2_CancelledDate' => $prDetails->pr_Branch_Level2_CancelledDate,
                //         'pr_Branch_Level2_Cancelled_Remarks' => $prDetails->pr_Branch_Level2_Cancelled_Remarks,
                //         'pr_Branch_Level3_ApprovedBy' => $prDetails->pr_Branch_Level3_ApprovedBy,
                //         'pr_Branch_Level3_ApprovedDate' => $prDetails->pr_Branch_Level3_ApprovedDate,
                //         'pr_Branch_Level3_CancelledBy' => $prDetails->pr_Branch_Level3_CancelledBy,
                //         'pr_Branch_Level3_CancelledDate' => $prDetails->pr_Branch_Level3_CancelledDate,
                //         'pr_Branch_Level3_Cancelled_Remarks' => $prDetails->pr_Branch_Level3_Cancelled_Remarks,
                //         'pr_Branch_Level4_ApprovedBy' => $prDetails->pr_Branch_Level4_ApprovedBy,
                //         'pr_Branch_Level4_ApprovedDate' => $prDetails->pr_Branch_Level4_ApprovedDate,
                //         'pr_Branch_Level4_CancelledBy' => $prDetails->pr_Branch_Level4_CancelledBy,
                //         'pr_Branch_Level4_CancelledDate' => $prDetails->pr_Branch_Level4_CancelledDate,
                //         'pr_Branch_Level4_Cancelled_Remarks' => $prDetails->pr_Branch_Level4_Cancelled_Remarks,
                //         'item_Last_PR_Date' => $prDetails->item_Last_PR_Date,
                //         'item_Last_PR_Qty' => $prDetails->item_Last_PR_Qty,
                //         'item_Last_PR_UnitofMeasurement_Id' => $prDetails->item_Last_PR_UnitofMeasurement_Id,
                //         'filename' => $prDetails->filename,
                //         'filepath' => $prDetails->filepath,
                //         'item_status_id' => $prDetails->item_status_id,
                //         'is_submitted' => $prDetails->is_submitted,
                //         'prepared_supplier_id' => $prDetails->prepared_supplier_id,
                //         'approved_by_purchaser' => $prDetails->approved_by_purchaser,
                //         'cancelled_by_purchaser' => $prDetails->cancelled_by_purchaser,
                //         'approved_date_purchaser' => $prDetails->approved_date_purchaser,
                //         'cancelled_date_purchaser' => $prDetails->cancelled_date_purchaser,
                //         'cancelled_remarks_purchaser' => $prDetails->cancelled_remarks_purchaser,
                //     ]
                // );
            }
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
    
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }

    }
    
    public function updateIsRecommended(Request $request, $id){
        CanvasMaster::where('pr_request_details_id', $request->details_id)->update(['isRecommended' => 0]);
        CanvasMaster::where('id', $id)->update(['isRecommended' => !$request->is_recommended]);
        return response()->json(['message' => 'success'], 200);
    }
    
    public function destroy($id)
    {
        $canvas = CanvasMaster::with('attachments')->where('id', $id)->first();
        foreach ($canvas->attachments as $key => $attachment) {
            File::delete(public_path().$attachment->filepath);
            $attachment->delete();
        }
        $canvas->delete();

        if($canvas->isFreeGoods == 1){
            $prDetails = PurchaseRequestDetails::where('pr_request_id',$canvas->pr_request_id)->where('item_Id',$canvas->canvas_Item_Id)->first();
            $prDetails->delete();
        }
        
        return response()->json(['message' => 'success'], 200);
    }
    
    public function submitCanvasItem(Request $request)
    {
        $authUser = Auth::user();
        $pr= PurchaseRequest::where('id', $request->prid)->first();
        if($pr->isdietary == '1' || $pr->ismedicine == '1'){
            $pr->where('id', $request->prid)->update([
                'pr_Purchaser_Status_Id' => true,
                'pr_Purchaser_UserId'=>$authUser->idnumber,
            ]);
        }
        $details = canvasMaster::where('pr_request_id',$request->prid)->whereIn('pr_request_details_id', $request->items)->where('isRecommended',1)->get();
        canvasMaster::where('pr_request_id',$request->prid)->update([
            'canvas_Document_CanvassBy'=>$authUser->idnumber
        ]);
        foreach($details as $row){
            PurchaseRequestDetails::where('id', $row->pr_request_details_id)->update([
                'is_submitted' => true,
                'recommended_supplier_id' => $row->id,
            ]);
        }
        return response()->json(['message' => 'success'], 200);
    }

    public function approveCanvasItem(Request $request)
    {
        $authUser = Auth::user();
        $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CTCR1'])->first();
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            
            foreach ($request->items as $key => $item) {
                $detail = PurchaseRequestDetails::with('purchaseRequest')->where('id', $item['item_id'])->first();
                if($item['status'] == true){
                    if($authUser->role->name == 'purchaser'){
                        $detail->recommendedCanvas()->update([
                            'canvas_Level1_ApprovedBy' => $authUser->idnumber,
                            'canvas_Level1_ApprovedDate' => Carbon::now(),
                        ]);
                    }else if($authUser->role->name == 'comptroller'){
                        $detail->recommendedCanvas()->update([
                            'canvas_Level2_ApprovedBy' => $authUser->idnumber,
                            'canvas_Level2_ApprovedDate' => Carbon::now(),
                            'canvas_Document_Approved_Number' => generateCompleteSequence($prefix, $number, $suffix, "")
                        ]);
                    }
                }else{
                    if($authUser->role->name == 'purchaser'){
                        $detail->recommendedCanvas()->update([
                            'canvas_Level1_CancelledBy' => $authUser->idnumber,
                            'canvas_Level1_CancelledDate' => Carbon::now(),
                            'canvas_Level1_Cancelled_Remarks' => $item['remarks'],
                        ]);
                    }else if($authUser->role->name == 'comptroller'){
                        $detail->recommendedCanvas()->update([
                            'canvas_Level2_CancelledBy' => $authUser->idnumber,
                            'canvas_Level2_CancelledDate' => Carbon::now(),
                            'canvas_Level2_Cancelled_Remarks' => $item['remarks'],
                        ]);
                    }
                    
                }
            }

            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
            ]);
    
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }

    }

    public function reconsiderCanvas(Request $request){
        $prid = $request->pr_id;
        $canvas = CanvasMaster::where('pr_request_id',$prid)->where('isRecommended',1)->first();
        if($canvas){
            $canvas->where('pr_request_id',$prid)->where('isRecommended',1)->update([
                'canvas_Level2_CancelledBy'=>null,
                'canvas_Level2_CancelledDate'=>null,
            ]);
        }
    }
}
