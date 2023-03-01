<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemGroup extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "itemInventoryGroup";

    public function categories(){
        return $this->hasMany(Itemcategories::class, 'invgroup_id', 'id');
    }

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'invgroup_id', 'id');
    }
}
