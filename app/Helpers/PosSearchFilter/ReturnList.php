<?php

namespace App\Helpers\PosSearchFilter;

use App\Models\POS\Orders;
use Illuminate\Support\Facades\Auth;
use App\Models\POS\ReturnTransaction;
use App\Models\BuildFile\Warehouseitems;

class ReturnList
{
    protected $model;
    public function __construct()
    {
        $this->model = ReturnTransaction::query();
    }

    public function searchable()
    {
        $this->model->with('refund_items','orders')->orderBy('id', 'desc');
        $this->searchColumns();
        $per_page = Request()->page ?? '1';
        return $this->model->paginate(12);
    }

    public function searchColumns()
    {
        if(isset(Request()->type)){
            $this->model->where('refund_status_id',Request()->type);
        }
        // $this->model->where('pick_list_number', 'LIKE', Request()->payload['keyword'].'%');
    }
}
