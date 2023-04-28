<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\POS\Customers;

class Customer
{
    protected $model;
    public function __construct()
    {
        $this->model = Customers::query();
    }

    public function searchable()
    {
        $this->searchColumns();
        $this->model->where('isActive', '1')->orderBy('id', 'desc');
        return $this->model->paginate(5);
    }

    public function searchColumns()
    {
        if (Request()->keyword) {
            $this->model->where(function ($query) {
                $query->where('customer_last_name', 'LIKE',Request()->keyword.'%');
                $query->orWhere('customer_first_name', 'LIKE',Request()->keyword.'%');
            });
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
