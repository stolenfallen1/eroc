<?php

namespace App\Models\BuildFile;

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
}
