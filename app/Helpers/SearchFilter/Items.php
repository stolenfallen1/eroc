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

  public function searchable()
  {
    $this->model->with('itemGroup', 'itemCategory', 'unit');
    $this->byBranch();
    $this->byWarehouse();
    $this->byCategory();
    $this->bySubCategory();
    $this->byInventoryGroup();
    $this->byTab();
    $this->searchColumns();
    $this->withWareHouseItems();
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchColumns()
  {
    $searchable = ['item_name', 'item_Description', 'item_SKU', 'item_Barcode'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
        }
      });
    }
  }

  private function withWareHouseItems(){
    if(Request()->wareHouseItems){
      $this->model->with('wareHouseItems');
    }
  }

  private function byTab()
  {
    if (Request()->tab) {
      $this->model->where('item_InventoryGroup_Id', Request()->tab);
    }
  }

  private function byWarehouse()
  {
    $warehouse = Request()->warehouse_id;
    if ($warehouse) {
      $this->model->whereHas('wareHouseItems', function ($q) use ($warehouse) {
        $q->where('warehouse_Id', $warehouse);
      });
      $this->model->with('wareHouseItem');
    }
  }

  private function byBranch()
  {
    $branch = Request()->branch_id;
    if ($branch) {
      $this->model->whereHas('wareHouseItems', function ($q) use ($branch) {
        $q->whereHas('warehouse', function($q1) use ($branch) {
          $q1->where('warehouse_Branch_Id', $branch);
        });
      });
      $this->model->with('wareHouseItem');
    }
  }

  private function byCategory()
  {
    $category_id = Request()->category_id;
    if ($category_id) {
      $this->model->where('item_Category_Id', $category_id);
    }
  }

  private function bySubCategory()
  {
    $subcategory_id = Request()->subcategory_id;
    if ($subcategory_id) {
      $this->model->where('item_SubCategory_Id', $subcategory_id);
    }
  }

  private function byInventoryGroup()
  {
    $item_InventoryGroup_Id = Request()->item_InventoryGroup_Id;
    if ($item_InventoryGroup_Id) {
      $this->model->where('item_InventoryGroup_Id', $item_InventoryGroup_Id);
    }
  }
}
