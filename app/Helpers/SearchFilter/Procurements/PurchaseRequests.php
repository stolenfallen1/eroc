<?php

namespace App\Helpers\SearchFilter\Procurements;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use Illuminate\Support\Facades\DB;
use App\Models\MMIS\procurement\PurchaseRequest;

class PurchaseRequests
{
  protected $model;
  protected $authUser;
  protected $role;

  public function __construct()
  {
    $this->model = PurchaseRequest::query();
    // $this->model = DB::connection('sqlsrv_mmis')->table('purchaseRequestMaster');
    $this->authUser = auth()->user();
    $this->role = new ParentRole();
  }

  public function searchable()
  {
    $this->model->where(function ($query) {
      $query->whereYear('created_at', '!=', 2022);
    });
    if ($this->role->purchaser() && Request()->department == '') {
      $this->model->whereIn('warehouse_Id', $this->authUser->departments);
    }
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
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
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
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

  private function byItemGroup()
  {
    if ($this->authUser->isDepartmentHead && $this->authUser->isConsultant) {
      if (count($this->authUser->assigneditemgroup) > 0) {
        $this->model->whereIn('invgroup_id', $this->authUser->assigneditemgroup);
      }
    } else {
      $group = Request()->item_group ? Request()->item_group : 1;
      if (Request()->item_group) {
        $this->model->where('invgroup_id', $group);
      }
    }
  }

  private function byCategory()
  {
    if ($this->authUser->isDepartmentHead && $this->authUser->isConsultant) {
      if (count($this->authUser->assingcategory) > 0) {
        $this->model->whereIn('item_Category_Id', $this->authUser->assingcategory);
      }
    } else {
      if (Request()->category) {
        $this->model->where('item_Category_Id', Request()->category);
      }
    }
  }

  private function bySubCategory()
  {
    if (Request()->subcategory) {
      $this->model->where('item_SubCategory_Id', Request()->subcategory);
    }
  }

  private function byPriority()
  {
    if (Request()->priority) {
      $this->model->where('pr_Priority_Id', Request()->priority);
    }
  }

  private function byRequestedDate()
  {
    if (Request()->requested_date) {
      $this->model->whereDate('pr_Transaction_Date', '>=', Carbon::parse(Request()->requested_date));
    }
  }

  private function byRequiredDate()
  {
    if (Request()->required_date) {
      $this->model->whereDate('pr_Transaction_Date', '<=', Carbon::parse(Request()->required_date));
    }
  }

  private function byYear()
  {
    if (!Request()->required_date || !Request()->requested_date) {
      $this->model->whereYear('created_at', Carbon::now()->year);
    }
  }

  private function byUser()
  {
    if ($this->role->staff() || $this->role->department_head()) {
      $this->model->where('branch_Id', $this->authUser->branch_id)->whereIn('warehouse_Id', $this->authUser->departments);
    }
  }

  private function byTab()
  {
    if (Request()->tab == 1) {
      $this->forApproval();
    } else if (Request()->tab == 2) {
      $this->forDepartmentHead();
    } else if (Request()->tab == 3) {
      $this->forConsultant();
    } else if (Request()->tab == 4) {
      $this->forAdministrator();
    } else if (Request()->tab == 5) {
      $this->forCanvas();
    } else if (Request()->tab == 6) {
      $this->canvasForApproval();
    } else if (Request()->tab == 7) {
      $this->approveCanvas();
    } else if (Request()->tab == 8) {
      $this->purchaserApproval();
    } else if (Request()->tab == 9) {
      $this->forPurchaseOrder();
    } else if (Request()->tab == 10) {
      $this->forVoidPr();
    } else if (Request()->tab == 11) {
      $this->forDeclinedPr();
    }
  }

  private function forVoidPr()
  {
    if (Request()->tab == 10) {
      $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null)
        ->where('invgroup_id', 2)->where('isvoid', 1);
      $this->model->with('purchaseOrder', 'purchaseRequestDetails.itemMaster');
    }
  }

  private function forApproval()
  {
    // Apply department head and staff role logic
    if ($this->role->staff() || $this->role->audit() || $this->role->purchaser()) {
      $this->model->whereIn('warehouse_Id', $this->authUser->departments)
        ->whereNull('pr_Purchaser_Status_Id')
        ->whereHas('purchaseRequestDetails', function ($query) {
          $query->whereNull('pr_DepartmentHead_ApprovedBy')
            ->whereNull('pr_DepartmentHead_CancelledBy');
        })
        ->with('purchaseRequestDetails.itemMaster');
    } else if ($this->role->department_head()) {
      if ($this->role->pharmacy_warehouse()) {
        $this->model->where('pr_Purchaser_Status_Id', 1);
      }
      if ($this->role->isdietary() || $this->role->isdietaryhead()) {
        $this->model->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
        $this->model->where('pr_Purchaser_Status_Id', 1)->orWhereNull('pr_Purchaser_Status_Id');
        // 
      }
      $this->model->whereIn('warehouse_Id', $this->authUser->departments)
        ->whereHas('purchaseRequestDetails', function ($q1) {
          if ($this->role->isdietary() || $this->role->isdietaryhead()) {
            $q1->where(['recommended_supplier_id' => null]);
          }

          $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
        })
        ->with('purchaseRequestDetails.itemMaster', 'purchaseRequestDetails.changedRecommendedCanvas', 'purchaseRequestDetails.changedRecommendedCanvas.vendor');
    }
    // Apply administrator and corporate admin logic
    else if ($this->role->administrator() || $this->role->corp_admin()) {
      // Apply branch-specific logic

      // $this->model->where(function($q){
      //   $q->where(function($q1){
      //     $q1->where('branch_Id', '!=', 1)->where('branch_Id', $this->authUser->branch_id)->where('invgroup_id', 2);
      //   })->orWhere(function($q1){
      //     $q1->where('branch_Id', 1)->where('invgroup_id', '!=', 2);
      //   });
      // })
      // ->where('pr_DepartmentHead_ApprovedBy', '!=', null)->whereNull('pr_Branch_Level1_ApprovedBy')
      // ->whereNull('pr_Branch_Level1_CancelledBy');
      // $this->model->with(['purchaseRequestDetails'=>function ($q){
      //   $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      // }]);

      if ($this->authUser->branch_id != '1') {
        $this->model->whereNotNull('pr_Branch_Level2_ApprovedBy');
      } else {
        $this->model->whereNull('pr_Branch_Level2_ApprovedBy')
          ->whereNull('pr_Branch_Level1_CancelledBy')
          ->whereNull('ismedicine');
      }

      $this->model->whereNotNull('pr_DepartmentHead_ApprovedBy')
        ->whereNull('pr_Branch_Level1_ApprovedBy')
        ->with(['purchaseRequestDetails' => function ($query) {
          $query->with('itemMaster')
            ->whereNotNull('pr_DepartmentHead_ApprovedBy');
        }]);
    }
    // Apply consultant role logic
    else if ($this->role->consultant()) {
      if (Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant) {
        if (Request()->branch == '1') {
          $this->model->whereIn('warehouse_Id', $this->authUser->departments)
            ->whereHas('purchaseRequestDetails', function ($query) {
              $query->whereNull('pr_DepartmentHead_ApprovedBy')
                ->whereNull('pr_DepartmentHead_CancelledBy');
            })
            ->with('purchaseRequestDetails.itemMaster');
        } else {
          $this->model->whereHas('purchaseRequestDetails', function ($query) {
            $query->whereNotNull('pr_DepartmentHead_ApprovedBy')
              ->whereNull('pr_Branch_Level2_ApprovedBy')
              ->whereNull('pr_DepartmentHead_CancelledBy');
          })
            ->whereNull('pr_Branch_Level1_ApprovedBy')
            ->whereNull('pr_Branch_Level1_CancelledBy')
            ->whereNotNull('pr_DepartmentHead_ApprovedBy')
            ->with('purchaseRequestDetails.itemMaster');
        }
      } else {
        $this->model->where(function ($query) {
          $query->whereNotNull('pr_DepartmentHead_ApprovedBy');
        })
          ->whereNull('pr_Branch_Level2_ApprovedBy')
          ->whereNull('pr_Branch_Level2_CancelledBy')
          ->where('invgroup_id', 2)
          ->whereNull('pr_Branch_Level1_ApprovedBy')
          ->whereNull('pr_Branch_Level1_CancelledBy')
          ->whereNotNull('pr_DepartmentHead_ApprovedBy')
          ->with('purchaseRequestDetails.itemMaster', 'purchaseRequestDetails.changedRecommendedCanvas', 'purchaseRequestDetails.changedRecommendedCanvas.vendor');
      }
    }
    // Apply the common ordering
    $this->model->orderBy('created_at', 'desc');
  }

  private function forApproval1()
  {
    if ($this->role->department_head() || $this->role->staff()) {
      $this->model->whereIn('warehouse_Id', $this->authUser->departments)
        ->whereHas('purchaseRequestDetails', function ($q1) {
          $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
        });

      $this->model->with('purchaseRequestDetails.itemMaster');
    } else if ($this->role->administrator() || $this->role->corp_admin()) {
      if ($this->authUser->branch_id != '1') {
        $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null);
      } else {
        $this->model->whereNull('pr_Branch_Level2_ApprovedBy');
        $this->model->whereNull('pr_Branch_Level1_CancelledBy');
        $this->model->whereNull('ismedicine');
      }

      $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      $this->model->whereNull('pr_Branch_Level1_ApprovedBy');

      $this->model->with(['purchaseRequestDetails' => function ($q) {
        $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }]);
    } else if ($this->role->consultant()) {
      if (Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant) {
        if (Request()->branch == '1') {
          $this->model->whereIn('warehouse_Id', $this->authUser->departments)
            ->whereHas('purchaseRequestDetails', function ($q1) {
              $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
            });

          $this->model->with('purchaseRequestDetails.itemMaster');
        } else {
          // $this->model->whereIn('warehouse_Id', $this->authUser->departments);
          $this->model->whereHas('purchaseRequestDetails', function ($q1) {
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where(['pr_Branch_Level2_ApprovedBy' => null])->where(['pr_DepartmentHead_CancelledBy' => null]);
          });
          $this->model->with('purchaseRequestDetails.itemMaster');
          $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])->where('pr_DepartmentHead_ApprovedBy', '!=', null);
        }
      } else {
        $this->model->where(function ($q) {
          $q->where(function ($q1) {
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
          })->orWhere(function ($q1) {
            $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
          });
        })->where(['pr_Branch_Level2_ApprovedBy' => null, 'pr_Branch_Level2_CancelledBy' => null]);
        $this->model->where('invgroup_id', 2)->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }

      $this->model->with(['purchaseRequestDetails' => function ($q) {
        $q->with('itemMaster');
      }]);
    }
    $this->model->orderBy('created_at', 'desc');
  }

  private function forDepartmentHead()
  {

    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    $this->model->with(['purchaseRequestDetails' => function ($q) {
      $q->with(['itemMaster', 'changedRecommendedCanvas', 'changedRecommendedCanvas.vendor']) // Correctly reference nested relationships
        ->whereNotNull('pr_DepartmentHead_ApprovedBy'); // Simplified where condition
    }]);
    // $this->model->with(['purchaseRequestDetails' => function ($q) {
    //   $q->with('itemMaster','purchaseRequestDetails.changedRecommendedCanvas','purchaseRequestDetails.changedRecommendedCanvas.vendor')
    //     ->where(function ($q) {
    //       $q->where(function ($q1) {
    //         $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null);
    //       });
    //   });

    // }]);

    if ($this->role->administrator()) {
      $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
    } else {
      if ($this->authUser->isDepartmentHead && $this->authUser->isConsultant) {
        $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      } else {
        $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null)->whereIn('warehouse_Id', $this->authUser->departments);
      }
    }
    $this->model->orderBy('created_at', 'desc');
  }

  private function forConsultant()
  {
    $this->model->where('pr_Branch_Level2_ApprovedBy', '!=', null)->where(function ($q1) {
      $q1->where('isvoid', 0)->orWhereNull('isvoid');
    });

    $this->model->with('purchaseOrder', 'purchaseRequestDetails.itemMaster');

    $this->model->orderBy('created_at', 'desc');
  }

  private function forAdministrator()
  {
    $this->model->where(function ($q) {
      // $q->where(function($q1){
      //   $q1->where('branch_Id', '!=', 1)->where('branch_Id', $this->authUser->branch_id)->where('invgroup_id', 2);
      // })->orWhere(function($q1){
      //   $q1->where('branch_Id', 1)->where('invgroup_id', '!=', 2);
      // });
    })->where('pr_Branch_Level1_ApprovedBy', '!=', null);

    $this->model->orderBy('created_at', 'desc');
  }


  private function forCanvas2()
  {

    $this->model->with(['purchaseRequestDetails' => function ($query) {
      $query->where(function ($q) {
        if ($this->model->where('ismedicine', 1)->exists()) {
          // When `ismedicine` is 1, keep the condition commented (if needed)
          // $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
          //   ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        } elseif ($this->model->whereNull('ismedicine')->exists()) {
          // Apply this condition when `ismedicine` is null
          $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
            ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        } else {
          // Apply conditions when `ismedicine` is anything other than 1 or null
          $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
            ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
        }
      })
        ->where(function ($q) {

          if ($this->model->where('ismedicine', 1)->exists()) {
            // When `ismedicine` is 1, keep the condition commented (if needed)
            // $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
            //   ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
          } elseif ($this->model->whereNull('ismedicine')->exists()) {
            // Apply this condition when `ismedicine` is null
            $q->where('is_submitted', true)->orWhereNull('is_submitted');
          } else {
            // Apply conditions when `ismedicine` is anything other than 1 or null
            $q->where('is_submitted', true)->orWhereNull('is_submitted');
          }
        });
    }])
      ->where(function ($query) {
        $query->where(function ($q) {

          if ($this->model->where('ismedicine', 1)->exists()) {
            // When `ismedicine` is 1, keep the condition commented (if needed)
            // $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
            //   ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
          } elseif ($this->model->whereNull('ismedicine')->exists()) {
            // Apply this condition when `ismedicine` is null
            $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
              ->where('invgroup_id', '!=', 2)
              ->whereHas('purchaseRequestDetails', function ($q2) {
                $q2->whereNotNull('pr_Branch_Level1_ApprovedBy');
              });
          } else {
            // Apply conditions when `ismedicine` is anything other than 1 or null
            $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
              ->where('invgroup_id', '!=', 2)
              ->whereHas('purchaseRequestDetails', function ($q2) {
                $q2->whereNotNull('pr_Branch_Level1_ApprovedBy');
              });
          }
        })
          ->orWhere(function ($q) {

            if ($this->model->where('ismedicine', 1)->exists()) {
              // When `ismedicine` is 1, keep the condition commented (if needed)
              // $q->whereNotNull('pr_Branch_Level1_ApprovedBy')
              //   ->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
            } elseif ($this->model->whereNull('ismedicine')->exists()) {
              // Apply this condition when `ismedicine` is null
              $q->whereNotNull('pr_Branch_Level2_ApprovedBy')
                ->whereHas('purchaseRequestDetails', function ($q2) {
                  $q2->whereNotNull('pr_Branch_Level2_ApprovedBy');
                });
            } else {
              // Apply conditions when `ismedicine` is anything other than 1 or null
              $q->whereNotNull('pr_Branch_Level2_ApprovedBy')
                ->whereHas('purchaseRequestDetails', function ($q2) {
                  $q2->whereNotNull('pr_Branch_Level2_ApprovedBy');
                });
            }
          });
      })
      ->whereHas('purchaseRequestDetails', function ($q) {
        $q->whereHas('canvases', function ($q2) {
          $q2->whereDoesntHave('purchaseRequestDetail', function ($q3) {
            $q3->where('is_submitted', true);
          })
            ->whereNull('canvas_Level1_ApprovedBy')
            ->whereNull('canvas_Level1_CancelledBy')
            ->whereNull('canvas_Level2_ApprovedBy')
            ->whereNull('canvas_Level2_CancelledBy');
        })
          ->orWhereDoesntHave('canvases');
      });
    if ($this->authUser->branch_id != 1) {
      $this->model->where('branch_id', $this->authUser->branch_id);
    }

    if ($this->role->staff() || $this->role->department_head()) {
      $this->model->where('isPerishable', 1);
    } else {
      $this->model->where(function ($q2) {
        $q2->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      });
    }

    $this->model->orderBy('created_at', 'desc');
  }

  private function forCanvas()
  {
    // if($this->role->purchaser() && Request()->item_group =='' && Request()->department ==''){
    //   $this->model->whereIn('invgroup_id', $this->authUser->assigneditemgroup);
    // } 
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    // if($this->role->department_head())

    $this->model->with(['purchaseRequestDetails' => function ($q) {
      if ($this->model->where('ismedicine', 1)->exists() || $this->model->where('isdietary', 1)->exists()) {
      } else {
        if (Request()->branch === $this->authUser->branch_id) {
          $q->where(function ($q2) {

            if ($q2->where('isdietary', 1)->exists() || $q2->where('ismedicine', 1)->exists()) {
              $q2->whereNull('pr_Branch_Level1_ApprovedBy')->orWhereNull('pr_Branch_Level2_ApprovedBy');
            } else {
              $q2->whereNotNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level2_ApprovedBy');
            }
          })->where(function ($q2) {
            $q2->where('is_submitted', false)->orWhereNull('is_submitted');
          });
        }else{
           $q->where('pr_Branch_Level2_ApprovedBy','!=',null)->where('is_submitted', false)->orWhereNull('is_submitted');
        }
      }
    }]);

    $this->model->where(function ($q1) {
      $q1->where(function ($q2) {
        $q2->where(function ($q3) {
          $q3->whereNull('pr_Branch_Level1_ApprovedBy')->orWhereNotNull('pr_Branch_Level1_ApprovedBy');
        })->where('invgroup_id', '!=', 2)->whereHas('purchaseRequestDetails', function ($q3) {
          $q3->where(function ($q5) {
            if ($q5->where('isdietary', 1)->exists() || $q5->where('ismedicine', 1)->exists()) {
              $q5->where(['pr_Branch_Level1_ApprovedBy' => null])->orWhereNotNull('pr_Branch_Level1_ApprovedBy');
            } else {
              $q5->whereNotNull('pr_Branch_Level1_ApprovedBy');
            }
          });
        });
      })
        ->orWhere(function ($q2) {
          $q2->where(function ($q3) {
            $q3->where('pr_Branch_Level2_ApprovedBy', '!=', null)->orWhereNull('pr_Branch_Level2_ApprovedBy');
          })
            ->where('invgroup_id', 2)->whereHas('purchaseRequestDetails', function ($q3) {
              $q3->where(function ($q4) {
                if ($q4->where('isdietary', 1)->exists() || $q4->where('ismedicine', 1)->exists()) {
                  $q4->where('pr_Branch_Level2_ApprovedBy', '!=', null)->orWhereNull('pr_Branch_Level2_ApprovedBy');
                } else {
                  $q4->where('pr_Branch_Level2_ApprovedBy', '!=', null);
                }
              });
            });
        });
    });



    $this->model->whereHas('purchaseRequestDetails', function ($q1) {
      $q1->whereHas('canvases', function ($q1) {
        $q1->whereDoesntHave('purchaseRequestDetail', function ($q2) {
          $q2->where('is_submitted', true);
        });
        // $q->where(['canvas_Level1_ApprovedBy' => null, 'canvas_Level1_CancelledBy' => null, 'canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
      })->orWhereDoesntHave('canvases');
    });

    if ($this->authUser->branch_id != 1) {
      $this->model->where('branch_id', $this->authUser->branch_id);
    }

    if ($this->role->staff() || $this->role->department_head()) {
      $this->model->where('isPerishable', 1);
    } else {
      $this->model->where(function ($q2) {
        $q2->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      });
    }

    $this->model->orderBy('created_at', 'desc');
  }


  private function canvasForApproval()
  {
    // if($this->role->isdietary() || $this->role->isdietaryhead()){
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    // } 
    $this->model->where(function ($q) {
      $q->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
    });

    $this->model->whereHas('purchaseRequestDetails', function ($q) {
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function ($q1) {
          $q1->where(function ($q2) {
            // $q2->where('canvas_Branch_Id', '!=', 1)->where('canvas_Level1_ApprovedBy', '!=', null)
            // ->orWhere('canvas_Branch_Id', 1);
          })->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
        });
    });
    $this->model->with(['purchaseRequestDetails' => function ($q) {
      $q->with('recommendedCanvas.vendor')
        ->where(function ($query) {
          $query->whereHas('recommendedCanvas', function ($query1) {
            $query1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
          });
        })->where('is_submitted', true);
    }]);

    $this->model->orderBy('created_at', 'desc');
  }

  private function approveCanvas()
  {
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    $this->model->with('purchaseOrder')->where('pr_Branch_Level1_ApprovedBy', '!=', null)->whereHas('purchaseRequestDetails', function ($q) {
      $q->where('is_submitted', true)
        ->whereHas('recommendedCanvas', function ($q1) {
          $q1->where('canvas_Level2_ApprovedBy', '!=', null);
        });
    });

    $this->model->with(['purchaseRequestDetails' => function ($q) {
      $q->with('recommendedCanvas.vendor')
        ->where(function ($query) {
          $query->whereHas('recommendedCanvas', function ($query1) {
            $query1->whereNotNull('canvas_Level2_ApprovedBy');
          });
        })->where('is_submitted', true);
    }]);
    if ($this->role->staff() || $this->role->department_head()) {
      $this->model->where('isPerishable', 1);
    } else {
      $this->model->where(function ($q2) {
        $q2->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      });
    }
    $this->model->orderBy('created_at', 'desc');
  }

  private function purchaserApproval()
  {
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    $this->model->where('branch_Id', '!=', 1)
      ->where('pr_Branch_Level1_ApprovedBy', '!=', null)
      ->whereHas('purchaseRequestDetails', function ($q) {
        $q->where('is_submitted', true)
          ->whereHas('recommendedCanvas', function ($q1) {
            $q1->where('canvas_Level1_ApprovedBy', null)->where('canvas_Level1_CancelledBy', null)
              ->where('canvas_Branch_Id', '!=', 1);
          });
      });

    $this->model->orderBy('created_at', 'asc');
  }

  private function forPurchaseOrder()
  {
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
    $this->model->where(function ($q1) {
      $q1->where('pr_Branch_Level1_ApprovedBy', '!=', null)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
    })->with(['purchaseRequestDetails' => function ($q) {
      $q->with('recommendedCanvas.vendor')
        ->where(function ($query) {

          $query->whereHas('recommendedCanvas', function ($query1) {
            $query1->whereNotNull('canvas_Level2_ApprovedBy');
          });
        })->where('is_submitted', true);
    }])

      ->whereHas('purchaseRequestDetails', function ($q) {
        $q->where('is_submitted', true)
          ->whereHas('recommendedCanvas', function ($q1) {
            $q1->where(function ($q2) {
              $q2->where('canvas_Level2_ApprovedBy', '!=', null);
            });
          })->whereDoesntHave('purchaseOrderDetails');
      });
    if ($this->authUser->branch_id != 1) $this->model->where('branch_id', $this->authUser->branch_id);
    if ($this->role->staff() || $this->role->department_head()) {
      $this->model->where('isPerishable', 1);
    } else {
      $this->model->where(function ($q) {
        $q->where('isPerishable', 0)->orWhere('isPerishable', NULL);
      })->where(function ($q) {
        $q->where('isvoid', 0)->orWhereNull('isvoid');
      });
    }

    $this->model->orderBy('created_at', 'desc');
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
}
