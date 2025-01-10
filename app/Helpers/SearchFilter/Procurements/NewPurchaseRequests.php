<?php

namespace App\Helpers\SearchFilter\Procurements;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use Illuminate\Support\Facades\DB;
use App\Models\MMIS\procurement\VwForCanvasPR;

class NewPurchaseRequests
{
  protected $model;
  protected $authUser;
  protected $role;

  public function __construct()
  {
    $this->model = VwForCanvasPR::query();
    // $this->model = DB::connection('sqlsrv_mmis')->table('purchaseRequestMaster');
    $this->authUser = auth()->user();
    $this->role = new ParentRole();
  }

  public function searchable()
  {
    if ($this->role->purchaser() && Request()->department == '') {
      $this->model->whereIn('warehouse_Id', $this->authUser->departments);
    }
    $this->searchableColumns();
    $this->byBranch();
    $this->byDepartment();

    $per_page = Request()->per_page;
    $this->model->orderBy('id','desc');
    if ($per_page == '-1') return $this->model->paginate($per_page);
    return $this->model->paginate($per_page);
  }

  public function searchableColumns()
  {
    $searchable = ['pr_number'];

    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where('pr_Document_Number', 'LIKE', '%' . $keyword . '%');
      // $this->model->where(function ($q) use ($keyword, $searchable) {
      //   foreach ($searchable as $column) {
      //     if ($column == 'pr_number')
      //       $q->whereRaw("CONCAT(pr_Document_Prefix,'',pr_Document_Number,'',pr_Document_Suffix) = ?", $keyword)
      //         ->orWhere('pr_Document_Number', 'LIKE', '' . $keyword . '%');
      //     // $q->where('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
      //     else
      //       $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
      //   }
      // });
    } else {
      $this->model->where('pr_Document_Number', 'LIKE', '000%');
    }
  }

  // $this->authUser->role->name == 'consultant' ||
  private function byBranch()
  {
    if ($this->role->department_head() || $this->role->staff()) {
      $this->model->where('branch_Id', $this->authUser->branch_id);
    } else if ($this->role->consultant()) {

      $branch = Request()->branch == 1 ? $this->authUser->branch_id : Request()->branch;
      if (Request()->branch) {
        $this->model->where('branch_Id', $branch);
      } else {
        // $this->model->whereNotIn('branch_Id',[1]);
        $this->model->where('branch_Id', $branch);
      }
    }
    if (Request()->branch) {
      $this->model->where('branch_Id', Request()->branch);
    }
  }

  private function byDepartment()
  {
    if ($this->role->department_head() || $this->role->staff()) {
      $this->model->whereIn('warehouse_Id', $this->authUser->departments);
    }
    if (Request()->department) {

      $this->model->where('warehouse_Id', Request()->department);
    } else {

      if ($this->authUser->branch_id != 1 && $this->authUser->isDepartmentHead && $this->authUser->isConsultant) {
        // $this->model->whereIn('warehouse_Id', $this->authUser->departments);
      }

      if ($this->role->department_head() || $this->role->staff()) {
        $this->model->whereIn('warehouse_Id', $this->authUser->departments);
      }
    }
  }

 

  public function forDeclinedPr()
  {
    if ($this->role->administrator()) {
      $this->model->with(['purchaseRequestDetails' => function ($q1) {
        $q1->with('itemMaster')->whereNotNull('pr_Branch_Level1_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function ($q) {
        $q->where('pr_Branch_Level1_CancelledBy', $this->authUser->idnumber);
      });
    } else if ($this->role->department_head()) {
      $this->model->with(['purchaseRequestDetails' => function ($q1) {
        $q1->with('itemMaster')->whereNotNull('pr_DepartmentHead_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function ($q) {
        // $q->where('pr_DepartmentHead_CancelledBy', $this->authUser->idnumber);
        $q->whereNotNull('pr_DepartmentHead_CancelledBy');
      });
    } else if ($this->role->consultant()) {
      $this->model->with(['purchaseRequestDetails' => function ($q1) {
        $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy');
      }])->whereHas('purchaseRequestDetails', function ($q) {
        $q->where('pr_Branch_Level2_CancelledBy', $this->authUser->idnumber);
      });
    } else {
      if ($this->role->staff() || $this->role->department_head()) {
        $this->model->with(['purchaseRequestDetails' => function ($q1) {
          $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy')
            ->orWhereNotNull('pr_DepartmentHead_CancelledBy')->orWhereNotNull('pr_Branch_Level1_CancelledBy');
        }])
          ->where(function ($q1) {
            $q1->where('invgroup_id', '!=', 2)->where(function ($q2) {
              $q2->whereHas('purchaseRequestDetails', function ($q) {
                $q->whereNotNull('pr_Branch_Level1_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
              });
            });
          })->orWhere(function ($q1) {
            $q1->where('invgroup_id', 2)->where(function ($q2) {
              $q2->whereHas('purchaseRequestDetails', function ($q) {
                $q->whereNotNull('pr_Branch_Level2_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
              });
            });
          })->where('warehouse_Id', $this->authUser->warehouse_id);
      } else {
        $this->model->with(['purchaseRequestDetails' => function ($q1) {
          $q1->with('itemMaster')->whereNotNull('pr_Branch_Level2_CancelledBy')
            ->orWhereNotNull('pr_DepartmentHead_CancelledBy')->orWhereNotNull('pr_Branch_Level1_CancelledBy');
        }])
          ->where(function ($q1) {
            $q1->where('invgroup_id', '!=', 2)->where(function ($q2) {
              $q2->whereHas('purchaseRequestDetails', function ($q) {
                $q->whereNotNull('pr_Branch_Level1_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
              });
            });
          })->orWhere(function ($q1) {
            $q1->where('invgroup_id', 2)->where(function ($q2) {
              $q2->whereHas('purchaseRequestDetails', function ($q) {
                $q->whereNotNull('pr_Branch_Level2_CancelledBy')->orWhereNotNull('pr_DepartmentHead_CancelledBy');
              });
            });
          });
      }
    }
  }

  public function forDeclinedCanvas()
  {
    if (!$this->role->comptroller()) {
      $this->model->where('warehouse_Id', $this->authUser->warehouse_id);
    }
    $this->model->with(['purchaseRequestDetails' => function ($q) {
      $q->with(['itemMaster', 'changedRecommendedCanvas', 'changedRecommendedCanvas.vendor', 'recommendedCanvas.vendor', 'recommendedCanvas']) // Correctly reference nested relationships
        ->whereNotNull('pr_DepartmentHead_ApprovedBy'); // Simplified where condition
    }]);
    $this->model->whereHas('purchaseRequestDetails', function ($q1) {
      $q1->whereHas('recommendedCanvas', function ($q1) {
        $q1->where(function ($q2) {
          $q2->where('canvas_Level2_CancelledBy', '!=', null);
        });
      });
    });
  }
}
