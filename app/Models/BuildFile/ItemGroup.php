<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemGroup extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "CDG_CORE.dbo.invItemInventoryGroup";

    public function categories(){
        return $this->hasMany(Itemcategories::class, 'invgroup_id', 'id');
    }

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'invgroup_id', 'id');
    }

    public function warehouses(){
        return $this->belongsToMany(Warehouses::class, 'invItemInventoryGroup_mappings', 'ItemInventoryGroup_id', 'warehouse_id');
    }
}
