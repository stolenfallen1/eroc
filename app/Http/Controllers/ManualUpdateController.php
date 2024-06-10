<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\ManualUpdateCanvass;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\Delivery;
use App\Models\MMIS\inventory\DeliveryItems;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\PurchaseRequestDetails;

class ManualUpdateController extends Controller
{
    public function update_purchaserequest(Request $request)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $payload = $request->payload;
            $pr = PurchaseRequestDetails::where('id',$payload['pr_detail_id'])->first();
            if($pr){
                $pr->update(
                    [
                        'item_Id' =>$payload['new_item_id'],
                        'item_Request_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Request_UnitofMeasurement_Id' =>$payload['new_item_id'],
                        
                        'item_Request_Department_Approved_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Request_Department_Approved_UnitofMeasurement_Id' =>$payload['new_unit'],

                        'item_Branch_Level1_Approved_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Branch_Level1_Approved_UnitofMeasurement_Id' =>$payload['new_unit'],

                        'item_Branch_Level2_Approved_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Branch_Level2_Approved_UnitofMeasurement_Id' =>$payload['new_unit'],

                        'item_Branch_Level3_Approved_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Branch_Level3_Approved_UnitofMeasurement_Id' =>$payload['new_unit'],

                        'item_Branch_Level4_Approved_Qty' =>$payload['new_item_Request_Qty'],
                        'item_Branch_Level4_Approved_UnitofMeasurement_Id' =>$payload['new_unit'],
                    ]
                );
            }
           
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }


    public function update_purchasecanvass(Request $request)
    {
        
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $payload = $request->payload;
            $itemDetails = Itemmasters::findOrfail($payload['new_canvas_Item_id']);

            $discount_amount = 0;
            $vat_amount = 0;
            $total_amount = $payload['new_canvas_item_amount'] * $payload['new_canvas_Item_Qty'];
            
            if($payload['new_canvas_item_discount_percent']){
                $discount_amount = $total_amount * ($payload['new_canvas_item_discount_percent'] / 100);
            }

            if($payload['new_canvas_item_vat_rate']){
                if($itemDetails->isVatable == 1 || $itemDetails->isVatable != null){
                    $vat_amount = ($total_amount - $discount_amount) * ($payload['new_canvas_item_vat_rate'] / 100);
                }
            }
            $canvas_item_total_amount =($total_amount - $discount_amount) + $vat_amount;

            ManualUpdateCanvass::updateOrCreate(
                [
                    'id' => $payload['canvas_id'],
                ],
                [
                    'canvas_Item_Id' =>$payload['new_canvas_Item_id'],
                    'canvas_Item_Qty' =>$payload['new_canvas_Item_Qty'],
                    'canvas_Item_UnitofMeasurement_Id' =>$payload['new_unit'],
                    'canvas_item_amount' =>$payload['new_canvas_item_amount'],
                    'canvas_item_discount_percent' =>$payload['new_canvas_item_discount_percent'],
                    'canvas_item_vat_rate' =>$payload['new_canvas_item_vat_rate'],
                    'canvas_item_discount_amount' => $discount_amount,
                    'canvas_item_vat_amount' => $vat_amount,
                    'canvas_item_net_amount' => $canvas_item_total_amount,
                    'canvas_item_total_amount' => $canvas_item_total_amount,
                    'vendor_id' =>$payload['new_vendor'],
                    'canvas_Document_CanvassBy' =>$payload['new_canvaser'],
                ]
            );
            
            $detail = PurchaseOrderDetails::updateOrCreate(
                [
                    'pr_detail_id' => $payload['pr_request_details_id'],
                    'po_Detail_item_id' => $payload['new_canvas_Item_id'],
                    'canvas_id' => $payload['canvas_id']
                ],
                [
                    'po_Detail_item_id' => $payload['new_canvas_Item_id'],
                    'po_Detail_item_listcost' => $canvas_item_total_amount,
                    'po_Detail_item_qty' => $payload['new_canvas_Item_Qty'],
                    'po_Detail_item_unitofmeasurement_id' => $payload['new_unit'],
                    'po_Detail_item_discount_percent' => $payload['new_canvas_item_discount_percent'],
                    'po_Detail_item_discount_amount' => $discount_amount,
                    'po_Detail_vat_percent' => $payload['new_canvas_item_vat_rate'],
                    'po_Detail_vat_amount' => $vat_amount,
                    'po_Detail_net_amount' => round($canvas_item_total_amount, 4), 
                ]
            );

            $podetails = PurchaseOrderDetails::where('po_id',$detail->po_id)->get();
            $po_Document_discount_percent = 0;
            $po_Document_discount_amount = 0;
            $po_Document_vat_percent = 0;
            $po_Document_vat_amount = 0;
            $po_Document_total_net_amount = 0;
            $po_Document_total_gross_amount = 0;
            if(sizeof($podetails) > 0){
                foreach($podetails as $podetail){
                    $po_Document_vat_percent += $podetail->po_Detail_vat_percent;
                    $po_Document_discount_percent += $podetail->po_Detail_item_discount_percent;
                    $po_Document_total_gross_amount += $payload['new_canvas_item_amount'] * $podetail->po_Detail_item_qty;
                    $po_Document_discount_amount += $podetail->po_Detail_item_discount_amount;
                    $po_Document_vat_amount += $podetail->po_Detail_vat_amount;
                    $po_Document_total_net_amount += $podetail->po_Detail_net_amount;
                }
            }

            $po = purchaseOrderMaster:: where('id', $detail->po_id)->first();
            if($po){
                $po->update(
                    [
                        'po_Document_vendor_id' => (int)$payload['new_vendor'],
                        'po_Document_due_date_unit' => (int)$payload['new_unit'],
                        'po_Document_total_gross_amount' => $po_Document_total_gross_amount,
                        'po_Document_discount_percent' =>  $po_Document_discount_percent,
                        'po_Document_discount_amount' =>  $po_Document_discount_amount,
                        'po_Document_vat_percent' => $po_Document_vat_percent,
                        'po_Document_vat_amount' => $po_Document_vat_amount,
                        'po_Document_total_net_amount' => $po_Document_total_net_amount,
                    ]
                );       
            }
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }
}
