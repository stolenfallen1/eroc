<?php

namespace App\Helpers\SearchFilter\Procurements;

use App\Models\MMIS\procurement\PurchaseRequest;

class PurchaseRequests
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = PurchaseRequest::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){
    $this->model->with('warehouse', 'status', 'category', 'subcategory', 
    'purchaseRequestDetails.itemMaster', 'purchaseRequestAttachments', 'user');
    $this->forApproval();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function forApproval(){
    $for_approval = Request()->for_approval;
    if($for_approval && $for_approval!=null){
      if($this->authUser->role->name == 'department head'){
        $this->model->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);
      }else if( $this->authUser->role->name == 'administrator' ){
        $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
        ->where(function ($q){
          $q->where('pr_DepartmentHead_ApprovedBy', '!=', null)->orWhere('pr_DepartmentHead_CancelledBy', '!=', null);
        });
      }
    }
  }

}