<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouseitems extends Model
{
    use HasFactory;

    protected $table = "warehouseitems";

    public function itemMaster()
    {
        return $this->belongsTo(Itemmasters::class, 'item_Id', 'id');
    }
}
