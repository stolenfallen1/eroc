<?php

namespace App\Http\Controllers\MMIS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Helpers\SearchFilter\Procurements\Canvases;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class CanvasController extends Controller
{
    public function index()
    {
        return (new Canvases)->searchable();
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $discount_amount = 0;
        $total_amount = $request->canvas_item_amount * $request->canvas_Item_Qty;
        if($request->canvas_discount_percent || $request->attachments != null){
            $discount_amount = $total_amount * ($request->canvas_discount_percent / 100);
        }

        $canvas = CanvasMaster::create([
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

        return response()->json(['message' => 'success'], 200);
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


}
