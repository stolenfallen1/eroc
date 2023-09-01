<?php

namespace App\Helpers\Buildfile;

use App\Models\BuildFile\Branchs;
use Illuminate\Support\Facades\Auth;

class Branch
{
    protected $model;
    public function __construct()
    {
        $this->model = Branchs::query();
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
