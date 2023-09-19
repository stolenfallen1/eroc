<?php

namespace App\Models\BuildFile\FMS;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostCenter extends Model
{
    use HasFactory;
    protected $table = 'fmsCostCenter';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function department(){
        return $this->belongsTo(Warehouses::class, 'department_id', 'id');
    }
}
