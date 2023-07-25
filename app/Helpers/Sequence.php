<?php

namespace App\Helpers;

use App\Models\BuildFile\SystemSequence;
use Illuminate\Support\Facades\Auth;

class Sequence{

  protected $model;
  public function __construct()
  {
    $this->model = SystemSequence::query();
  }

  public function create($description){
    $this->model->create([
      'branch_id' => Auth::user()->branch_id,
      'system_id' => 1,
      'subsystem_id' => 1,
      'module_id' => 1,
      'submodule_id' => 1,
      'seq_prefix' => 1,
    ]);
  }

}