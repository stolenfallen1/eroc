<?php

namespace App\Http\Controllers\MMIS;

use App\Http\Controllers\Controller;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getPurchaseRequestCount(){
        $approved_count = PurchaseRequest::whereHas('purchaseRequestDetails', function($q1){
            $q1->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $declined_count = PurchaseRequest::whereHas('purchaseRequestDetails', function($q1){
            $q1->whereNotNull('pr_Branch_Level1_CancelledBy')->orWhereNotNull('pr_Branch_Level2_CancelledBy')
            ->orWhereNotNull('pr_DepartmentHead_CancelledBy');
        })->whereDoesntHave('purchaseRequestDetails', function($q1){
            $q1->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $pending_count = PurchaseRequest::whereDoesntHave('purchaseRequestDetails', function($q1){
            $q1->where(function($q2){
                $q2->whereNotNull('pr_DepartmentHead_ApprovedBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
            })->orWhere(function($q2){
                $q2->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level1_CancelledBy')
                ->orWhereNotNull('pr_Branch_Level2_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_CancelledBy');
            });
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        return [ $approved_count, $pending_count, $declined_count ];
    }

    public function getCanvasCount(){
        $approved_count = PurchaseRequest::whereHas('purchaseRequestDetails', function($q1){
            $q1->whereHas('recommendedCanvas', function($q2){
                $q2->whereNotNull('canvas_Level2_ApprovedBy');
            });
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $declined_count = PurchaseRequest::whereHas('purchaseRequestDetails', function($q1){
            $q1->whereHas('recommendedCanvas', function($q2){
                $q2->whereNotNull('canvas_Level2_CancelledBy');
            });
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $pending_count = PurchaseRequest::whereHas('purchaseRequestDetails', function($q1){
            $q1->where(function($q2){
                $q2->where(function($q3){
                    $q3->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
                })->whereDoesntHave('canvases');
            })->orWhereHas('recommendedCanvas', function($q2){
                $q2->whereNull('canvas_Level2_ApprovedBy')->whereNull('canvas_Level2_CancelledBy');
            });
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        return [ $approved_count, $pending_count, $declined_count ];
    }

    public function getPurchaseOrderCount(){
        $approved_count = purchaseOrderMaster::where(function($q1){
            $q1->where('po_Document_total_net_amount', '>', 99999)->whereHas('details', function($q2){
                $q2->whereNotNull('ysl_approved_by');
            });
        })->orWhere(function($q1){
            $q1->where('po_Document_total_net_amount', '<', 100000)->whereHas('details', function($q2){
                $q2->where(function($q3){
                    $q3->whereNotNull('corp_admin_approved_by')->orWhereNotNull('admin_approved_by');
                });
            });
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $declined_count = purchaseOrderMaster::where(function($q1){
            $q1->where('po_Document_total_net_amount', '>', 99999)->whereNotNull('ysl_cancelled_by');
        })->orWhere(function($q1){
            $q1->where('po_Document_total_net_amount', '<', 100000)->where(function($q2){
                $q2->whereNotNull('corp_admin_cancelled_by')->orWhereNotNull('admin_cancelled_by');
            });
        })->orWhere(function($q1){
            $q1->whereNotNull('comptroller_cancelled_by');
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        $pending_count = purchaseOrderMaster::where(function($q1){
            $q1->where('po_Document_total_net_amount', '>', 99999)->whereNull('ysl_cancelled_by')
            ->whereNull('ysl_approved_by');
        })->orWhere(function($q1){
            $q1->where('po_Document_total_net_amount', '<', 100000)->where(function($q2){
                $q2->whereNull('corp_admin_cancelled_by')->whereNull('admin_cancelled_by')
                ->whereNull('corp_admin_approved_by')->whereNull('admin_approved_by');
            });
        })->orWhere(function($q1){
            $q1->whereNull('comptroller_cancelled_by')->whereNull('comptroller_approved_by');
        })->whereMonth('updated_at', Request()->month)->whereYear('updated_at', Request()->year)->count();

        return [ $approved_count, $pending_count, $declined_count ];
    }
    
}
