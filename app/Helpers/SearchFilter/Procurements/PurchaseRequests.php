<?php

namespace App\Helpers\SearchFilter\Procurements;

use Carbon\Carbon;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class PurchaseRequests
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = PurchaseRequest::query();
    // $this->model = DB::connection('sqlsrv_mmis')->table('purchaseRequestMaster');
    $this->authUser = auth()->user();
  }

  public function searchable(){
    $this->model->with('warehouse', 'status', 'category', 'subcategory', 'purchaseRequestAttachments', 'user', 'itemGroup');
    $this->byBranch();
    $this->byTab();
    $this->byItemGroup();
    $this->byCategory();
    $this->bySubCategory();
    $this->byPriority();
    $this->byRequestedDate();
    $this->byRequiredDate();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }
  
  // $this->authUser->role->name == 'consultant' ||
  private function byBranch(){
    if($this->authUser->role->name == 'department head' || $this->authUser->role->name == 'staff' || 
      $this->authUser->role->name == 'administrator' || $this->authUser->role->name == '')
    {
      $this->model->where('branch_Id', $this->authUser->branch_id);
    }
  }

  private function byItemGroup()
  {
    if(Request()->item_group){
      $this->model->where('invgroup_id', Request()->item_group);
    }
  }

  private function byCategory(){
    if(Request()->category){
      $this->model->where('item_Category_Id', Request()->category);
    }
  }

  private function bySubCategory(){
    if(Request()->subcategory){
      $this->model->where('item_SubCategory_Id', Request()->subcategory);
    }
  }
  
  private function byPriority(){
    if(Request()->priority){
      $this->model->where('pr_Priority_Id', Request()->priority);
    }
  }

  private function byRequestedDate(){
    if(Request()->requested_date){
      $this->model->whereDate('pr_Transaction_Date', Carbon::parse(Request()->requested_date));
    }
  }

  private function byRequiredDate(){
    if(Request()->required_date){
      $this->model->whereDate('pr_Transaction_Date_Required', Carbon::parse(Request()->required_date));
    }
  }

  private function byTab(){
    if(Request()->tab == 1){
      $this->forApproval();
    }
    else if (Request()->tab == 2){
      $this->forDepartmentHead();
    }
    else if (Request()->tab == 3){
      $this->forConsultant();
    }
    else if (Request()->tab == 4){
      $this->forAdministrator();
    }
    else if (Request()->tab == 5){
      $this->forCanvas();
    }
    else if (Request()->tab == 6){
      $this->canvasForApproval();
    }
    else if (Request()->tab == 7){
      $this->approveCanvas();
    }
    else if (Request()->tab == 8){
      $this->purchaserApproval();
    }
    else if (Request()->tab == 9){
      $this->forPurchaseOrder();
    }
  }

  private function forApproval(){
    if($this->authUser->role->name == 'department head' || $this->authUser->role->name == 'staff' 
      || $this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){

      $this->model->where('warehouse_Id', $this->authUser->warehouse_id)
      ->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);

      $this->model->with('purchaseRequestDetails.itemMaster');

    }else if( $this->authUser->role->name == 'administrator' ){

      $this->model->where(function($q){
        $q->where(function($q1){
          $q1->where('branch_Id', '!=', 1)->where('branch_Id', $this->authUser->branch_id)->where('invgroup_id', 2);
        })->orWhere(function($q1){
          $q1->where('branch_Id', 1)->where('invgroup_id', '!=', 2);
        });
      })->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
      ->where('pr_DepartmentHead_ApprovedBy', '!=', null);

      // $this->model
      // ->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
      // ->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('invgroup_id', '!=', 2);

      $this->model->with(['purchaseRequestDetails'=>function ($q){
        $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }]);

    }else if( $this->authUser->role->name == 'consultant' ){

      $this->model->where(function($q){
        $q->where(function($q1){
          $q1->where('branch_Id', '!=', 1)->where('pr_Branch_Level1_ApprovedBy', '!=', null);
        })->orWhere(function($q1){
          $q1->where('branch_Id', 1)->where('pr_DepartmentHead_ApprovedBy', '!=', null);
        });
      })->where(['pr_Branch_Level2_ApprovedBy' => null, 'pr_Branch_Level2_CancelledBy' => null])->where('invgroup_id', 2);

      // $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
      // ->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('invgroup_id', 2);

      $this->model->with(['purchaseRequestDetails'=>function ($q){
        $q->with('itemMaster')
        ->where(function($q){
          $q->where(function($q1){
            $q1->wherehas('purchaseRequest', function($q2){
              $q2->where('branch_Id', '!=', 1);
            })->where('pr_Branch_Level1_ApprovedBy', '!=', null);
          })->orWhere(function($q1){
            $q1->wherehas('purchaseRequest', function($q2){
              $q2->where('branch_Id', 1);
            })->where('pr_DepartmentHead_ApprovedBy', '!=', null);
          });
        });
      }]);

    }
  }

  private function forDepartmentHead(){
    $this->model->with(['purchaseRequestDetails'=>function ($q){
      $q->with('itemMaster')
      ->where(function($q){
        $q->where(function($q1){
          $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
        });
      });
    }]);
    if($this->authUser->role->name == 'administrator'){
      $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
    }else{
      $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('warehouse_Id', $this->authUser->warehouse_id);
    }
  }

  private function forConsultant(){
    $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null)->where('invgroup_id', 2);
  }

  private function forAdministrator(){
    $this->model->where(function($q){
      $q->where(function($q1){
        $q1->where('branch_Id', '!=', 1)->where('branch_Id', $this->authUser->branch_id)->where('invgroup_id', 2);
      })->orWhere(function($q1){
        $q1->where('branch_Id', 1)->where('invgroup_id', '!=', 2);
      });
    })->where('pr_Branch_Level1_ApprovedBy', '!=', null);
  }

  private function forCanvas(){

    $this->model->where(function($q1){
      $q1->where(function($q2){
        $q2->where('pr_Branch_Level1_ApprovedBy', '!=', null)->where('invgroup_id', '!=', 2)->whereHas('purchaseRequestDetails', function ($q3){
          $q3->where('pr_Branch_Level1_ApprovedBy', '!=', null);
        });
      })
      ->orWhere(function($q2){
        $q2->where('pr_Branch_Level2_ApprovedBy', '!=', null)->where('invgroup_id', 2)->whereHas('purchaseRequestDetails', function ($q3){
          $q3->where('pr_Branch_Level2_ApprovedBy', '!=', null);
        });
      });
    })->whereHas('purchaseRequestDetails', function ($q1){
      $q1->whereHas('canvases', function($q1){
        $q1->whereDoesntHave('purchaseRequestDetail', function($q2){
          $q2->where('is_submitted', true);
        });
        // $q->where(['canvas_Level1_ApprovedBy' => null, 'canvas_Level1_CancelledBy' => null, 'canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
      })->orWhereDoesntHave('canvases');
    });

    // $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function ($q){
    //   // $q->where('is_submitted', NULL)->orWhere('is_submitted', false)
    //   $q->where('pr_Branch_Level1_ApprovedBy', '!=', NULL)
    //   ->where(function($query){
    //     $query->whereHas('canvases', function($q1){
    //       $q1->whereDoesntHave('purchaseRequestDetail', function($q2){
    //         $q2->where('is_submitted', true);
    //       });
    //       // $q->where(['canvas_Level1_ApprovedBy' => null, 'canvas_Level1_CancelledBy' => null, 'canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
    //     })->orWhereDoesntHave('canvases');
    //   });
    // });

    if($this->authUser->branch_id != 1){
      $this->model->where('branch_id', $this->authUser->branch_id);
    }

    if($this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->where('isPerishable', 1);
    }else{
      $this->model->where(function($q2){
        $q2->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      });
    }
  }
  
  private function canvasForApproval(){
    $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function($q1){
          $q1->where(function($q2){
            $q2->where('canvas_Branch_Id', '!=', 1)->where('canvas_Level1_ApprovedBy', '!=', null)
            ->orWhere('canvas_Branch_Id', 1);
          })->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
        });
    });
  }

  private function approveCanvas(){
    $this->model->with('purchaseOrder')->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function($q1){
          $q1->where('canvas_Level2_ApprovedBy', '!=', null)->orWhere('canvas_Level2_CancelledBy', '!=', null);
        });
    });
  }

  private function purchaserApproval(){
    $this->model->where('branch_Id', '!=', 1)
      ->where('pr_Branch_Level1_ApprovedBy', '!=', null)
      ->whereHas('purchaseRequestDetails', function($q){
        $q->where('is_submitted', true)
          ->whereHas('recommendedCanvas', function($q1){
            $q1->where('canvas_Level1_ApprovedBy', null)->where('canvas_Level1_CancelledBy', null)
            ->where('canvas_Branch_Id', '!=', 1);
          });
      });
  }

  private function forPurchaseOrder(){
    $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
      ->whereHas('recommendedCanvas', function($q1){
        $q1->where('canvas_Level2_ApprovedBy', '!=', null)->orWhere('canvas_Level2_CancelledBy', '!=', null);
      })->whereDoesntHave('purchaseOrderDetails');
    });
    if($this->authUser->branch_id != 1) $this->model->where('branch_id', $this->authUser->branch_id);
    if($this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->where('isPerishable', 1);
    }else{
      $this->model->where(function($q){
        $q->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      });
    }
  }



}