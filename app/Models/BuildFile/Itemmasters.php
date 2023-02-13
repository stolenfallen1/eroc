<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itemmasters extends Model
{
    use HasFactory;
    protected $table = 'ItemMaster';

    public function wareHouseItems(){
        return $this->hasMany(Warehouseitems::class, 'item_Id', 'id');
    }
}
