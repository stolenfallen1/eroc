<?php

namespace App\Helpers\Buildfile;

use App\Models\BuildFile\Itemsubcategories;
use Illuminate\Support\Facades\Auth;

class SubCategoryFilter
{
    protected $model;
    public function __construct()
    {
        $this->model = Itemsubcategories::query();
    }

    public function searchable()
    {
        $this->searchColumns();
        $this->model->orderBy('id', 'desc');
        $this->model->with('categories')->where('parent_id',0)->where('category_id', Request()->id);
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
