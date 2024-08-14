<?php

namespace App\Models;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDeptAccess extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysDeptAccess';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['warehouse'];

    public function warehouse_details()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_id', 'id');
    }

    public function getWarehouseAttribute()
    {
        // Ensure that the relationship is loaded before accessing the attribute
        if ($this->relationLoaded('warehouse_details')) {
            return $this->warehouse_details->warehouse_description;
        }

        // Load the relationship and then access the attribute
        $warehouse = $this->warehouse_details()->first();
        return $warehouse ? $warehouse->warehouse_description : null;
    }

}
