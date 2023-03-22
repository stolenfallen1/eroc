<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;

class Items
{
    protected $model;
    public function __construct()
    {
        $this->model = Warehouseitems::query();
    }

    public function searchable()
    {
        $this->model->with('itemMaster', 'unit', 'itemMaster.brand');
        $this->byCategory();
        $this->searchColumns();
        $this->model->where('isactive', '1');
        $per_page = Request()->page ?? '1';
        return $this->model->paginate(12);
    }

    public function searchColumns()
    {
        if (isset(Request()->payload['keyword'])) {
            switch(Request()->payload['type']) {
                case 1:
                    $this->model->whereHas('itemMaster', function ($query) {
                        $query->where('item_name', 'LIKE', Request()->payload['keyword'].'%');
                        $query->orWhere('item_description', 'LIKE', Request()->payload['keyword'].'%');
                    });
                    break;
                case 2:
                    $this->model->whereHas('itemMaster', function ($query) {
                        $query->where('item_name', 'LIKE', Request()->payload['keyword'].'%');
                    });
                    break;
                case 3:
                    $this->model->whereHas('itemMaster', function ($query) {
                        $query->where('id', 'LIKE', Request()->payload['keyword'].'%');
                    });
                    break;
                case 4:
                    $this->model->whereHas('itemMaster', function ($query) {
                        $query->where('item_Barcode', 'LIKE', Request()->payload['keyword'].'%');
                    });
                    break;
            }
        }
    }

    private function byCategory()
    {
        $category_id = Request()->category_id;
        if ($category_id) {
            $this->model->where('item_Category_Id', $category_id);
        }
    }
}
