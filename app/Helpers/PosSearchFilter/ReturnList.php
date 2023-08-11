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
        if(isset(Request()->payload['type'])){
            $this->model->where('refund_status_id',Request()->payload['type']);
        }

        if(isset(Request()->ordernumber)){
            $digit = 10;
            $lenght = strlen(Request()->ordernumber);
            $zero = $digit - $lenght;
            $ordernumber = str_pad(0, $zero, "0", STR_PAD_LEFT).''.(int)Request()->ordernumber;
            $this->model->where('refund_transaction_number',$ordernumber);
        }
       
    }
}
