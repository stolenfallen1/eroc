<?php

namespace App\Helpers\SearchFilter\Procurements;

use App\Models\MMIS\procurement\PurchaseRequest;

class PurchaseRequests
{
  protected $model;
  public function __construct()
  {
    $this->model = PurchaseRequest::query();
  }

  public function searchable(){
    $this->model->with('warehouse', 'status', 'category', 'subcategory', 'purchaseRequestDetails.itemMaster', 'purchaseRequestAttachments');
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

}