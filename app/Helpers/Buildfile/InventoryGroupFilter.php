<?php

namespace App\Helpers\Buildfile;

use App\Models\BuildFile\ItemGroup;
use Illuminate\Support\Facades\Auth;

class InventoryGroupFilter
{
    protected $model;
    public function __construct()
    {
        $this->model = ItemGroup::query();
    }

    public function searchable()
    {
        $this->searchColumns();
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
    }
}
