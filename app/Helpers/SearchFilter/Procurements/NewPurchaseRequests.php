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
    $this->byTab();
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

 

  private function byTab()
  {
    if (Request()->tab == 5) {

      $this->model->where('is_submitted',0);
      $this->model->whereNull('canvasApprovedBy');
      $this->model->where('approver',0);
      $this->model->where('isPerishable',0);
      $this->model->where('isrecommended',1);

    } 
    else if (Request()->tab == 6) {

      $this->model->where('is_submitted',1);
      $this->model->whereNotNull('pr_DepartmentHead_ApprovedBy');
      $this->model->whereNull('canvasApprovedBy');
      $this->model->where('approver',0);
      // $this->model->where('isPerishable',0);
      $this->model->where('isrecommended',1);
      
    } 
    else if (Request()->tab == 7) {
      $this->model->whereNotNull('pr_DepartmentHead_ApprovedBy');
      $this->model->where('isPerishable',0);
      $this->model->where('adminOrConsultant',1);
      $this->model->where('canvasApprovedBy','!=','');

    } 
    else if (Request()->tab == 13) {

      $this->model->whereNull('pr_DepartmentHead_ApprovedBy');
      $this->model->where('canvasApprovedBy','!=','0');
      $this->model->where('approver',0);
      $this->model->where('isPerishable',0);
  
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
        ->with([
          'purchaseRequestDetails.itemMaster', 
          'purchaseRequestDetails.changedRecommendedCanvas' => function($q) {
              $q->where('isFreeGoods', null);
          },
          'purchaseRequestDetails.changedRecommendedCanvas.vendor'
      ]);
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
     
        $this->model
        ->whereNull('pr_Branch_Level2_ApprovedBy')
        ->whereNull('pr_Branch_Level2_CancelledBy')
        ->where('invgroup_id', 2)
        ->whereNull('pr_Branch_Level1_ApprovedBy')
        ->whereNull('pr_Branch_Level1_CancelledBy')
        ->whereNotNull('pr_DepartmentHead_ApprovedBy')
        ->with([
        'purchaseRequestDetails' => function ($query) {
            $query->where('pr_DepartmentHead_ApprovedBy', '!=', '')
                  ->whereNull('pr_DepartmentHead_CancelledBy');
        },
        'purchaseRequestDetails.itemMaster',
        'purchaseRequestDetails.changedRecommendedCanvas',
        'purchaseRequestDetails.changedRecommendedCanvas.vendor'
      ]);

      }
    }
    // Apply the common ordering
    $this->model->orderBy('created_at', 'desc');
  }
  private function forDeptHeadApproval()
  {
    $this->model->whereIn('warehouse_Id', $this->authUser->departments) 
      ->where('pr_Purchaser_Status_Id',1)
      ->whereHas('purchaseRequestDetails', function ($q1) {
        if ($this->role->isdietary() || $this->role->isdietaryhead()) {
          $q1->where(['recommended_supplier_id' => null]);
        }

        $q1->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
      })
      ->with('purchaseRequestDetails.itemMaster', 'purchaseRequestDetails.changedRecommendedCanvas', 'purchaseRequestDetails.changedRecommendedCanvas.vendor');
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
        ->whereNotNull('pr_DepartmentHead_ApprovedBy');// Simplified where condition
        
    }]);
    $this->model->whereHas('purchaseRequestDetails', function ($q1) {
      $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where(['pr_DepartmentHead_CancelledBy' => null]);
    });
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
    $this->model->whereHas('purchaseRequestDetails', function ($q1) {
      $q1->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where(['pr_DepartmentHead_CancelledBy' => null]);
    });
    $this->model->with('purchaseOrder', 'purchaseRequestDetails.itemMaster');

    $this->model->orderBy('created_at', 'desc');
  }

  private function forAdministrator()
  {
    if($this->role->consultant()){
      $this->model->whereIn('warehouse_Id', $this->authUser->departments);
    }
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
    
    $this->model->whereNull('pr_DepartmentHead_CancelledBy');
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
        } else {
          $q->where('pr_Branch_Level2_ApprovedBy', '!=', null)->where('is_submitted', false)->orWhereNull('is_submitted');
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
            ->where('invgroup_id', 2)
            ->whereHas('purchaseRequestDetails', function ($q3) {
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
 
  private function canvasForApproval1()
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
  private function canvasForApproval()
  {

    // $this->model->whereNotNull('approved_admin');
    $this->model->whereHas('newPurchaseRequestDetails', function ($q) {
      $q->where('is_submitted', true);
      $q->whereNull('canvas_Level2_ApprovedBy')->whereNull('canvas_Level2_CancelledBy')
        ->whereHas('NewrecommendedCanvas', function ($q1) {
          $q1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
        });
    });
    $this->model->with(['purchaseRequestDetails' => function ($q) {
      $q->with('recommendedCanvas.vendor')
        ->where('is_submitted', true)
        ->where(function ($query) {
          $query->whereHas('NewrecommendedCanvas', function ($query1) {
            $query1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
          });
        });
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
