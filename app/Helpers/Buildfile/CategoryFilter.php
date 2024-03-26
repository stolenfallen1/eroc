<?php

namespace App\Helpers\Buildfile;

use App\Models\BuildFile\Itemcategories;
use Illuminate\Support\Facades\Auth;

class CategoryFilter
{
    protected $model;
    public function __construct()
    {
        $this->model = Itemcategories::query();
    }

    public function searchable()
    {
        $this->searchColumns();
        $this->model->with('inventoryGroup');
        $this->model->orderBy('id', 'desc');
        $per_page = Request()->per_page ?? '1';
        return $this->model->paginate($per_page);
    }
    public function searchColumns()
    {
        $keyword = Request()->keyword ?? '';
        if ($keyword) {
            $this->model->where('name', 'LIKE', ''.$keyword.'%');
        }
        if (Request()->invgroup_id) {
            $this->model->where('invgroup_id',Request()->invgroup_id);
        }
    }
}
