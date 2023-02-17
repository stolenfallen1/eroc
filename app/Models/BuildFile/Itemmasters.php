<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseOrderDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Itemmasters extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'ItemMaster';
    protected $guarded = [];

    public function wareHouseItems(){
        return $this->hasMany(Warehouseitems::class, 'item_Id', 'id');
    }

    public function wareHouseItem(){
        return $this->hasOne(Warehouseitems::class, 'item_Id', 'id')->where('warehouse_Id', Request()->warehouse_id);
    }

    public function purchaseRequest(){
        return $this->hasMany(PurchaseOrderDetails::class, 'item_Id', 'id');
    }
}
