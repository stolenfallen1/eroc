<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\StockTransfer;

class StockTransfers {
  protected $model;
  protected $authUser;

  public function __construct()
  {
    $this->model = StockTransfer::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){

    $this->model->with('delivery', 'purchaseRequest', 'purchaseOrder', 'warehouseSender', 'warehouseReceiver', 'tranferBy', 'receivedBy');

    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);

  }

  
}