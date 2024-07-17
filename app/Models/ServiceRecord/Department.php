<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\TbcMaster;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['Description'];

    protected $table = 'tbcDepartment';

    // public function employees()
    // {
    //     return $this->hasMany(TbcMaster::class, 'DepartmentCode', 'Code');
    // }
}
