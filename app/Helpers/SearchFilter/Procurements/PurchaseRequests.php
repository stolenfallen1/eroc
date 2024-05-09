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
    $this->searchableColumns();
    $this->byBranch();
    $this->byDepartment();
    $this->byItemGroup();
    $this->byCategory();
    $this->bySubCategory();
    $this->byPriority();
    $this->byRequestedDate();
    $this->byRequiredDate();
    // $this->byYear();
    $this->byUser();
    $this->byTab();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchableColumns(){
    $searchable = ['pr_number'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
          foreach ($searchable as $column) {
              if ($column == 'pr_number')
                  $q->whereRaw("CONCAT(pr_Document_Prefix,'',pr_Document_Number,'',pr_Document_Suffix) = ?", $keyword)
                  ->orWhere('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
                  // $q->where('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
              else
                  $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
          }
      });
    }else{
      $this->model->where('pr_Document_Number', 'like', "000%");
    }
  }
  
  // $this->authUser->role->name == 'consultant' ||
  private function byBranch(){
    if($this->authUser->role->name == 'department head' || $this->authUser->role->name == 'staff' || $this->authUser->role->name == 'administrator' || $this->authUser->role->name == '')
    {
      $this->model->where('branch_Id', $this->authUser->branch_id);
    }
    else if($this->authUser->role->name == 'consultant'){
   
        $branch = Request()->branch == 1 ? $this->authUser->branch_id : Request()->branch;
        if(Request()->branch){
          $this->model->where('branch_Id', $branch);
        }else{
          // $this->model->whereNotIn('branch_Id',[1]);
          $this->model->where('branch_Id', $branch);
        }

    }
    if(Request()->branch){
      $this->model->where('branch_Id', Request()->branch);
    }
  }

  private function byDepartment(){
    if(Request()->department){
      $this->model->where('warehouse_Id',Request()->department);
    }else{
      if($this->authUser->branch_id != 1 && $this->authUser->isDepartmentHead && $this->authUser->isConsultant){
        $this->model->whereIn('warehouse_Id', $this->authUser->departments);
      }
      if($this->authUser->role->name == 'department head' || $this->authUser->role->name == 'staff' || $this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
        $this->model->whereIn('warehouse_Id', $this->authUser->departments);
      }
    }
  }

  private function byItemGroup()
  {
    if($this->authUser->isDepartmentHead && $this->authUser->isConsultant){
      if(count($this->authUser->assigneditemgroup) > 0){
         $this->model->whereIn('invgroup_id', $this->authUser->assigneditemgroup);
      }
    }else{
      $group = Request()->item_group ? Request()->item_group : 1;
      if(Request()->item_group){
        $this->model->where('invgroup_id', $group);
      }
    }
   
  }

  private function byCategory(){
    if($this->authUser->isDepartmentHead && $this->authUser->isConsultant){
      if(count($this->authUser->assingcategory) > 0){
        $this->model->whereIn('item_Category_Id',$this->authUser->assingcategory);
      }
    }else{
      if(Request()->category){
        $this->model->where('item_Category_Id', Request()->category);
      }
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
      $this->model->whereDate('pr_Transaction_Date', '>=', Carbon::parse(Request()->requested_date));
    }
  }

  private function byRequiredDate(){
    if(Request()->required_date){
      $this->model->whereDate('pr_Transaction_Date', '<=', Carbon::parse(Request()->required_date));
    }
  }

  private function byYear(){
    if(!Request()->required_date || !Request()->requested_date){
      $this->model->whereYear('created_at', Carbon::now()->year);
    }
  }

  private function byUser(){
    if($this->authUser->role->name == 'staff' || $this->authUser->role->name == 'department head'){
      $this->model->where('branch_Id', $this->authUser->branch_id)->whereIn('warehouse_Id', $this->authUser->departments);
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
    else if (Request()->tab == 10){
      $this->forVoidPr();
    }
    else if (Request()->tab == 11){
      $this->forDeclinedPr();
    }
  }

  private function forVoidPr(){
    if(Request()->tab == 10){
      $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null)
      ->where('invgroup_id', 2)->where('isvoid', 1);
      $this->model->with('purchaseOrder', 'purchaseRequestDetails.itemMaster');
    }
  }

  private function forApproval(){
    if($this->authUser->role->name == 'department head' || $this->authUser->role->name == 'staff' || $this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->whereIn('warehouse_Id', $this->authUser->departments)
      ->whereHas('purchaseRequestDetails', function($q1){
        $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
      });

      $this->model->with('purchaseRequestDetails.itemMaster');

    }else if( $this->authUser->role->name == 'administrator' ){
      if($this->authUser->branch_id != '1'){
        $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null);
      }else{

        $this->model->whereNull('pr_Branch_Level2_ApprovedBy');
        $this->model->whereNull('pr_Branch_Level1_CancelledBy');
      }

      $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      $this->model->whereNull('pr_Branch_Level1_ApprovedBy');
      
      $this->model->with(['purchaseRequestDetails'=>function ($q){
        $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }]);

    }else if( $this->authUser->role->name == 'consultant'){
      if(Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant){
        if(Request()->branch == '1'){
          $this->model->whereIn('warehouse_Id', $this->authUser->departments)
          ->whereHas('purchaseRequestDetails', function($q1){
            $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
          });

          $this->model->with('purchaseRequestDetails.itemMaster');
        }else{
          // $this->model->whereIn('warehouse_Id', $this->authUser->departments);
          $this->model->whereHas('purchaseRequestDetails', function($q1){
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where(['pr_Branch_Level2_ApprovedBy' => null])->where(['pr_DepartmentHead_CancelledBy' => null]);
          });
          $this->model->with('purchaseRequestDetails.itemMaster');
          $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])->where('pr_DepartmentHead_ApprovedBy', '!=', null);
        }
      }else{
        $this->model->where(function($q){
          $q->where(function($q1){
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
          })->orWhere(function($q1){
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
          });
        })->where(['pr_Branch_Level2_ApprovedBy' => null, 'pr_Branch_Level2_CancelledBy' => null]);
        $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }
      
        $this->model->with(['purchaseRequestDetails'=>function ($q){
          $q->with('itemMaster');
        }]);
        

    }
    $this->model->orderBy('created_at', 'desc');
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
      if($this->authUser->isDepartmentHead && $this->authUser->isConsultant){
        $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }else{
        $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null)->whereIn('warehouse_Id', $this->authUser->departments);
      }
    }
    $this->model->orderBy('created_at', 'desc');
  }

  private function forConsultant(){
    
    $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null)->where(function($q1){
        $q1->where('isvoid', 0)->orWhereNull('isvoid');
    });

    $this->model->with('purchaseOrder', 'purchaseRequestDetails.itemMaster');

    $this->model->orderBy('created_at', 'desc');
  }

  private function forAdministrator(){
    $this->model->where(function($q){
      // $q->where(function($q1){
      //   $q1->where('branch_Id', '!=', 1)->where('branch_Id', $this->authUser->branch_id)->where('invgroup_id', 2);
      // })->orWhere(function($q1){
      //   $q1->where('branch_Id', 1)->where('invgroup_id', '!=', 2);
      // });
    })->where('pr_Branch_Level1_ApprovedBy', '!=', null);

    $this->model->orderBy('created_at', 'desc');
  }

  private function forCanvas(){
      $this->model->with(['purchaseRequestDetails' => function($q){
        $q->where(function($q2){
          $q2->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        })->where(function($q2){
          $q2->where('is_submitted', true)->orWhereNull('is_submitted',false)->orWhereNull('is_submitted');
        });
      }])->where(function($q1){
      $q1->where(function($q2){
        $q2->whereNotNull('pr_Branch_Level1_ApprovedBy')
        ->where('invgroup_id', '!=', 2)
        ->whereHas('purchaseRequestDetails', function ($q3){
          $q3->whereNotNull('pr_Branch_Level1_ApprovedBy');
        });
      })
      ->orWhere(function($q2){
        // ->where('invgroup_id', 2)
        $q2->where('pr_Branch_Level2_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function ($q3){
          $q3->where('pr_Branch_Level2_ApprovedBy', '!=', null);
        });
      });
    })->whereHas('purchaseRequestDetails', function ($q1){
      $q1->whereHas('canvases', function($q1){
        $q1->whereDoesntHave('purchaseRequestDetail', function($q2){
          $q2->where('is_submitted', true);
        })->where(['canvas_Level1_ApprovedBy' => null, 'canvas_Level1_CancelledBy' => null, 'canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
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

    $this->model->orderBy('created_at', 'desc');
  }
  
  private function canvasForApproval(){
    $this->model->where(function($q){
      $q->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
    })->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function($q1){
          $q1->where(function($q2){
            // $q2->where('canvas_Branch_Id', '!=', 1)->where('canvas_Level1_ApprovedBy', '!=', null)
            // ->orWhere('canvas_Branch_Id', 1);
          })->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
        });
    });
    $this->model->with(['purchaseRequestDetails'=>function($q){
      $q->with('recommendedCanvas.vendor')
        ->where(function($query){
            $query->whereHas('recommendedCanvas', function($query1){
                $query1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
            });
        })->where('is_submitted', true);
    }]);
    $this->model->orderBy('created_at', 'desc');
  }

  private function approveCanvas(){
    $this->model->with('purchaseOrder')->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function($q1){
          $q1->where('canvas_Level2_ApprovedBy', '!=', null);
        });
    });

    $this->model->with(['purchaseRequestDetails'=>function($q){
      $q->with('recommendedCanvas.vendor')
        ->where(function($query){
            $query->whereHas('recommendedCanvas', function($query1){
                $query1->whereNotNull('canvas_Level2_ApprovedBy');
            });
        })->where('is_submitted', true);
    }]);

    $this->model->orderBy('created_at', 'desc');
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

      $this->model->orderBy('created_at', 'asc');
  }

  private function forPurchaseOrder(){
    $this->model->where(function($q1){
      $q1->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
    })->with(['purchaseRequestDetails'=>function($q){
      $q->with('recommendedCanvas.vendor')
        ->where(function($query){
            $query->whereHas('recommendedCanvas', function($query1){
                $query1->whereNotNull('canvas_Level2_ApprovedBy');
            });
        })->where('is_submitted', true);
    }])
    
    ->whereHas('purchaseRequestDetails', function($q){
      $q->where('is_submitted', true)
      ->whereHas('recommendedCanvas', function($q1){
        $q1->where(function($q2){
          $q2->where('canvas_Level2_ApprovedBy', '!=', null);
        });
      })->whereDoesntHave('purchaseOrderDetails');
    });
    if($this->authUser->branch_id != 1) $this->model->where('branch_id', $this->authUser->branch_id);
    if($this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->where('isPerishable', 1);
    }else{
      $this->model->where(function($q){
        $q->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      })->where(function($q){
        $q->where('isvoid', 0)->orWhereNull('isvoid');
      });
    }

    $this->model->orderBy('created_at', 'desc');
  }

  public function forDeclinedPr(){
    if($this->authUser->role->name == 'administrator'){
      $this->model->with(['purchaseRequestDetails' => function($q1){
        $q1->with('itemMaster')->whereNotNull('pr_Branch_Level1_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function($q){
        $q->where('pr_Branch_Level1_CancelledBy', $this->authUser->idnumber);
      });
    }
    else if ($this->authUser->role->name == 'department head'){
      $this->model->with(['purchaseRequestDetails' => function($q1){
        $q1->with('itemMaster')->whereNotNull('pr_DepartmentHead_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function($q){
        // $q->where('pr_DepartmentHead_CancelledBy', $this->authUser->idnumber);
        $q->whereNotNull('pr_DepartmentHead_CancelledBy');
      });
    }
    else if ($this->authUser->role->name == 'consultant'){
      $this->model->with(['purchaseRequestDetails' => function($q1){
        $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function($q){
        $q->where('pr_Branch_Level2_CancelledBy', $this->authUser->idnumber);
      });
    }else{
      if($this->authUser->role->name == 'staff' || $this->authUser->role->name == 'department head'){
        $this->model->with(['purchaseRequestDetails' => function($q1){
          $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy')
          ->orWhereNotNull('pr_DepartmentHead_CancelledBy')->orWhereNotNull('pr_Branch_Level1_CancelledBy');
        }])
        ->where(function($q1){
          $q1->where('invgroup_id', '!=', 2)->where(function($q2){
            $q2->whereHas('purchaseRequestDetails', function($q){
              $q->whereNotNull('pr_Branch_Level1_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
            });
          });
        })->orWhere(function($q1){
          $q1->where('invgroup_id', 2)->where(function($q2){
            $q2->whereHas('purchaseRequestDetails', function($q){
              $q->whereNotNull('pr_Branch_Level2_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
            });
          });
          
        })->where('warehouse_Id', $this->authUser->warehouse_id);
        
      }else{
        $this->model->with(['purchaseRequestDetails' => function($q1){
          $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy')
          ->orWhereNotNull('pr_DepartmentHead_CancelledBy')->orWhereNotNull('pr_Branch_Level1_CancelledBy');
        }])
        ->where(function($q1){
          $q1->where('invgroup_id', '!=', 2)->where(function($q2){
            $q2->whereHas('purchaseRequestDetails', function($q){
              $q->whereNotNull('pr_Branch_Level1_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
            });
          });
        })->orWhere(function($q1){
          $q1->where('invgroup_id', 2)->where(function($q2){
            $q2->whereHas('purchaseRequestDetails', function($q){
              $q->whereNotNull('pr_Branch_Level2_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
            });
          });
          
        });
      }
    }
  }

}