<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\Delivery;
use Carbon\Carbon;

class Deliveries
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = Delivery::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){
    $this->model->with('status', 'vendor', 'warehouse');
    $this->byTab();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function byTab()
  {
    if(Request()->tab == 1){
      $this->model->where('rr_Status', 5);
    }else if( Request()->tab == 2){
      $this->model->where('rr_Status', 11);
    }
  }

}