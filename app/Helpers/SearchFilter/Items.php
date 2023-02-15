<?php

namespace App\Helpers\SearchFilter;

use App\Models\BuildFile\Itemmasters;

class Items
{
  protected $model;
  public function __construct()
  {
    $this->model = Itemmasters::query();
  }

  public function searchable(){
    $this->byWarehouse();
    $this->byCategory();
    $this->bySubCategory();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function byWarehouse(){
    $warehouse = Request()->warehouse_id;
    if($warehouse){
      $this->model->whereHas('wareHouseItems', function($q) use($warehouse){
        $q->where('warehouse_Id', $warehouse);
      });
      $this->model->with('wareHouseItem');
    }
  }

  private function byCategory(){
    $category_id = Request()->category_id;
    if($category_id){
      $this->model->where('item_Category_Id', $category_id);
    }
  }

  private function bySubCategory(){
    $subcategory_id = Request()->subcategory_id;
    if($subcategory_id){
      $this->model->where('item_SubCategory_Id', $subcategory_id);
    }
  }

}
