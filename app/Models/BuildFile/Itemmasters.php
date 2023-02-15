<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Itemmasters extends Model
{
    use HasFactory;
    protected $table = 'ItemMaster';

    public function wareHouseItems(){
        return $this->hasMany(Warehouseitems::class, 'item_Id', 'id');
    }

    public function wareHouseItem(){
        return $this->hasOne(Warehouseitems::class, 'item_Id', 'id')->where('warehouse_Id', Request()->warehouse_id);
    }
}
