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
        return 0;
        // $model = PurchaseRequest::whereNull('pr_DepartmentHead_CancelledBy');
        // if($this->role->purchaser()){
        //     $model->whereIn('invgroup_id', Auth()->user()->assigneditemgroup);
        // }
        // $model->where(function($query) {
        //     $query->whereYear('created_at', '!=', 2022);
        // })->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
        // $model->whereHas('purchaseRequestDetails', function($q){
        //     $q->where('is_submitted', true)->whereHas('recommendedCanvas', function($q1){
        //         $q1->whereNotNull('canvas_Level2_ApprovedBy');
        //     })->whereDoesntHave('purchaseOrderDetails');
        // });
        // $model->where(function($q1){
        //     $q1->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
        // })->whereHas('purchaseRequestDetails', function($q){
        // $q->where('is_submitted', true)
        // ->whereHas('recommendedCanvas', function($q1){
        //     $q1->where('canvas_Level2_ApprovedBy', '!=', null);
        // })->whereDoesntHave('purchaseOrderDetails');
        // });
        // if(Auth()->user()->role->name == 'dietary' || Auth()->user()->role->name == 'dietary head'){
        //     $model->where('isPerishable', 1);
        // }else{
        //     $model->where('isPerishable', 0)->orWhereNull('isPerishable');
        // }
        
        // $model->where('pr_Document_Number', 'like', "%000%");
        // if(Auth::user()->branch_id != 1) $model->where('branch_id', Auth::user()->branch_id); 
        // return $model->count();
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
        
        $discount_amount = $request->discount_amount;
        $vat_amount = $request->vat_amount;
        $total_amount = $request->total_amount;
        
        $canvas_item_total_amount = $request->tota_net_amount;

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $itemid = isset($request->canvas_Old_Item_Id) ? $request->canvas_Old_Item_Id : $request->canvas_Item_Id;

            $checkcanvas = CanvasMaster::where('pr_request_id',$request->pr_request_id)->where('canvas_Item_Id',$itemid)->where('vendor_id',$request->vendor_id)->first();
            $freegood = NULL;
            if($request->isFreeGoods== true || $request->isFreeGoods == 1){
                $freegood = 1;
            }
            $freegoods = $freegood;
            if($freegoods){
                $canvas = CanvasMaster::create(
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
                    'canvas_item_total_amount' => $total_amount,
                    'canvas_item_discount_percent' => $request->canvas_discount_percent,
                    'canvas_item_discount_amount' => $discount_amount,
                    'canvas_item_net_amount' => $canvas_item_total_amount,
                    'canvas_lead_time' => $request->canvas_lead_time,
                    'canvas_remarks' => $request->canvas_remarks,
                    'currency_id' => $request->currency_id,
                    'canvas_item_vat_rate' => $request->canvas_item_vat_rate,
                    'canvas_item_vat_amount' => $vat_amount,
                    'vat_type' => $request->vat_type,
                    'discount_type' => $request->discount_type ?? 2,
                    'isFreeGoods' => $freegoods,
                    'isRecommended' => $checkcanvas ? $checkcanvas->isRecommended : 0,
                    'terms_id'=>  $request->terms_id ?? 10,
                ]);
            }else{
                $canvas = CanvasMaster::updateOrCreate(
                    [
                        'pr_request_id' => $request->pr_request_id,
                        'canvas_Item_Id' => $itemid,
                        'vendor_id' => $request->vendor_id,
                        'isFreeGoods' => $freegoods
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
                    'canvas_item_total_amount' => $total_amount,
                    'canvas_item_discount_percent' => $request->canvas_discount_percent,
                    'canvas_item_discount_amount' => $discount_amount,
                    'canvas_item_net_amount' => $canvas_item_total_amount,
                    'canvas_lead_time' => $request->canvas_lead_time,
                    'canvas_remarks' => $request->canvas_remarks,
                    'currency_id' => $request->currency_id,
                    'canvas_item_vat_rate' => $request->canvas_item_vat_rate,
                    'canvas_item_vat_amount' => $vat_amount,
                    'vat_type' => $request->vat_type,
                    'discount_type' => $request->discount_type ?? 2,
                    'isFreeGoods' => $freegoods,
                    'isRecommended' => $checkcanvas ? $checkcanvas->isRecommended : 0,
                    'terms_id'=>  $request->terms_id ?? 10,
                ]);
            }
          
    
            if (isset($request->attachments) && $request->attachments != null && sizeof($request->attachments) > 0) {
                foreach($request->attachments as $key => $attachment){
                    $file = storeDocument($attachment, "canvas/attachments", $key);
                    $canvas->attachments()->create([
                        'filepath' => $file[0],
                        'filename' => $file[2]
                    ]);
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
            return response()->json(["error" => $e->getMessage()], 200);
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
        $details = canvasMaster::where('pr_request_id',$request->prid)->whereIn('pr_request_details_id', $request->items)->whereNull('isFreeGoods')->where('isRecommended',1)->get();
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
