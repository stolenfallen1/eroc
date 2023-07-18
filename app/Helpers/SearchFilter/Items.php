<?php

namespace App\Helpers\SearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouses;

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
    $this->byCategory();
    $this->bySubCategory();
    $this->byInventoryGroup();
    $this->byTab();
    $this->searchColumns();
    $this->withWareHouseItems();
    $this->withWareHouseItem();
    $this->byWarehouse();
    $this->forLocation();
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

  private function forLocation(){
    if(Request()->for_location){
      $this->model->with('wareHouseItems')->whereDoesntHave('wareHouseItems', function ($query) {
        $query->where('warehouse_Id', Auth::user()->warehouse_id);
      });
    }
  }

  private function withWareHouseItems(){
    if(Request()->wareHouseItems){
      $this->model->with('wareHouseItems');
    }
  }

  private function withWareHouseItem(){
    if(Request()->wareHouseItem){
      $this->model->with('wareHouseItem');
    }
  }

  private function byTab()
  {
    if(Request()->tab=="0"){
      $warehouse = Warehouses::with('itemGroups')->findOrFail(Auth::user()->warehouse_id);
      $this->model->where('item_InventoryGroup_Id', $warehouse->itemGroups[0]->id);
    }else{
      if (Request()->tab) {
        $this->model->where('item_InventoryGroup_Id', Request()->tab);
      }
    }
  }

  private function byWarehouse()
  {
    $warehouse = Request()->warehouse_idd;
    if ($warehouse) {
      $this->model->with('wareHouseItems')->whereHas('wareHouseItems', function ($query) use ($warehouse) {
        $query->where('warehouse_Id', $warehouse);
      });
    }
  }

  private function byBranch()
  {
    return $branch = Request()->branch_id;
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
