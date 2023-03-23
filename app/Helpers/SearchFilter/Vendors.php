<?php

namespace App\Helpers\SearchFilter;

use App\Models\BuildFile\Vendors as BuildFileVendors;
use Illuminate\Support\Facades\Auth;

class Vendors
{
  protected $model;
  public function __construct()
  {
    $this->model = BuildFileVendors::query();
  }

  public function searchable()
  {
    $this->withInactive();
    $this->model->where('deleted_at', NULL);
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchColumns()
  {
    $searchable = ['vendor_Name', 'vendor_Address'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
        }
      });
    }
  }

  private function withInactive(){
    if(Request()->withInactive){
      
    }else{
      $this->model->where('isActive', 1);
    }
  }

}