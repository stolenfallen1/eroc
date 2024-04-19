<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\BuildFile\Vendors;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\BuildFile\SystemSequence;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Helpers\SearchFilter\Procurements\Canvases;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class CanvasController extends Controller
{
    public function index()
    {
        return (new Canvases)->searchable();
    }

    public function countForPO()
    {
        $model = PurchaseRequest::query();
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
        $vendor = Vendors::findOrfail($request->vendor_id);
        $pr = PurchaseRequest::findOrfail($request->pr_request_id);
       
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
        if($request->canvas_item_vat_rate){
            if($vendor->isVATInclusive == 0 || $vendor->isVATInclusive == null){
                $vat_amount = $total_amount * ($request->canvas_item_vat_rate / 100);
                $total_amount += $vat_amount;
            }else{
                $vat_amount = $total_amount * ($request->canvas_item_vat_rate / 100);
            }
        }
        if($request->canvas_discount_percent){
            $discount_amount = $total_amount * ($request->canvas_discount_percent / 100);
        }

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $canvas = CanvasMaster::create([
                'canvas_Document_Number' => $number,
                'canvas_Document_Prefix' => $prefix,
                'canvas_Document_Suffix' => $suffix,
                'canvas_Document_CanvassBy' => Auth::user()->idnumber,
                'canvas_Document_Transaction_Date' => Carbon::now(),
                'requested_date' => Carbon::parse($request->requested_date),
                'canvas_Branch_Id' => $authUser->branch_id,
                'canvas_Warehouse_Group_Id' => $authUser->warehouse->warehouse_Group_Id,
                'canvas_Warehouse_Id' => $authUser->warehouse->warehouse_Group_Id,
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
                'canvas_item_net_amount' => $total_amount - $discount_amount,
                'canvas_lead_time' => $request->canvas_lead_time,
                'canvas_remarks' => $request->canvas_remarks,
                'currency_id' => $request->currency_id,
                'canvas_item_vat_rate' => $request->canvas_item_vat_rate,
                'canvas_item_vat_amount' => $vat_amount,
                'isFreeGoods' => $request->isFreeGoods,
                'isRecommended' => 0,
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
        return response()->json(['message' => 'success'], 200);
    }
    
    public function submitCanvasItem(Request $request)
    {
        PurchaseRequestDetails::whereIn('id', $request->items)->update([
            'is_submitted' => true
        ]);
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


}
