<?php

namespace App\Helpers\PosSearchFilter;

use App\Models\POS\vwWarehouseItems;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;

class Items
{
    protected $model;
    public function __construct()
    {
        $this->model = vwWarehouseItems::query();
    }

    public function searchable()
    {
        $this->byCategory();
        $this->searchColumns();
        $this->branch();
        // $this->warehouse();
        $this->model->where('isactive', '1');
        $this->model->orderby('item_name', 'asc');
        $per_page = Request()->per_page ?? '1';
        return $this->model->paginate($per_page);
    }

    public function searchColumns()
    {
        
        if (isset(Request()->keyword)) {
            $this->model->where('item_name', 'LIKE',Request()->keyword.'%');
        }
    }

    public function byCategory()
    {
        $category_id = Request()->category ?? '';
        if ($category_id != 0) {
            $this->model->where('item_Category_Id',  Request()->category);
        }
    }

    public function warehouse()
    {
        $departmentid = Request()->departmentid ?? '';
        if ($departmentid) {
            $this->model->where('warehouse_Id',Request()->departmentid);
        }else{
            $this->model->where('warehouse_Id',Auth()->user()->departmentid);
        }
    }

    public function branch()
    {
        $branchid = Request()->branchid ?? '';
        if ($branchid) {
            $this->model->where('branch_id',Request()->branchid);
        }else{
            $this->model->where('branch_id',Auth()->user()->branch_id);
        }
    }
}
