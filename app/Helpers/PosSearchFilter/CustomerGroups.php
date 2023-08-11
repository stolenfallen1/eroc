<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\POS\CustomerGroup;
class CustomerGroups
{
    protected $model;
    public function __construct()
    {
        $this->model = CustomerGroup::query();
    }

    public function searchable()
    {
        $this->model->where('isActive', '1')->orderBy('id', 'desc');
        return $this->model->paginate(5);
    }

    public function searchColumns()
    {
      
    }

}
