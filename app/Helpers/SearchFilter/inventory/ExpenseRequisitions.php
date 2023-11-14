<?php

namespace App\Helpers\SearchFilter\inventory;

use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\procurement\ExpenseIssuance;

class ExpenseRequisitions
{
  protected $model;
  protected $authUser;

  public function __construct()
  {
    $this->model = ExpenseIssuance::query();
    $this->authUser = auth()->user();
  }

  public function searchable()
  {

    $this->model->with('user', 'item', 'batch');

    $this->byWarehouse();

    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function byWarehouse()
  {
    $auth_user = Auth::user();

    $this->model->where('warehouse_id', $auth_user->warehouse_id)
    ->where('branch_id', $auth_user->branch_id);
  }

  
}
