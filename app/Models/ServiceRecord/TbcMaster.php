<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Department;

class TbcMaster extends Model
{
    use HasFactory;

    protected $table = 'tbcMaster';
    // protected $hidden = ['Photo'];

    public function department() {
        return $this->hasOne(Department::class, 'Code', 'DepartmentCode');
    }
}
