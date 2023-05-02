<?php

namespace App\Helpers\PosSearchFilter;

use Carbon\Carbon;
use App\Models\POS\Orders;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use App\Helpers\PosSearchFilter\Terminal;

class Orderlist
{
    protected $model;
    public function __construct()
    {
        $this->model = Orders::query();
    }

    public function searchable()
    {
        $this->model->with('customers','order_items','order_items.ItemBatch','payment','order_items.vwItem_details')->orderBy('id', 'desc');
        $this->searchColumns();
        $this->searchTerminal();
        $this->dateTransaction();
        $per_page = Request()->page ?? '1';
        return $this->model->paginate(12);
    }

    public function searchColumns()
    {
        if(isset(Request()->payload['type'])){
            $this->model->where('order_status_id',Request()->payload['type']);
        }
        $this->model->where('pick_list_number', 'LIKE', Request()->payload['keyword'].'%');
    }

    public function dateTransaction()
    {
        if(isset(Request()->payload['datetransaction'])){
            $this->model->whereDate('order_date',Request()->payload['datetransaction']);
        }else{
            // $this->model->whereDate('order_date',Carbon::now()->format('Y-m-d'));
        }
    }
    public function searchTerminal(){
        $user_terminal = (new Terminal)->TakeOrderTerminal();
       
        if(Auth::user()->role->name == 'Pharmacist Assistant'){
            $this->model->where('terminal_id',Auth()->user()->terminal_id);
        }else if(Auth::user()->role->name == 'Pharmacist Cashier'){ 
            $this->model->whereIn('terminal_id',$user_terminal);
        }
    }
    
}
