<?php

namespace App\Helpers\Tools;

use App\Models\MMIS\tools\AuditTrail;

class AuditTrails {

  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = AuditTrail::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){

    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }
}