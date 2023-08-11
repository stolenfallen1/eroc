<?php

namespace App\Models\POS;

use App\Models\BuildFile\ItemGroup;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use App\Models\POS\ItemCategoriesMappings;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemInventoryGroupMappings extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'invItemInventoryGroup_mappings';
    protected $guarded = [];
    public function InventoryGroup(){
        return $this->belongsTo(ItemGroup::class, 'ItemInventoryGroup_id', 'id');
    }
    public function CategoryGroup(){
        return $this->hasMany(ItemCategoriesMappings::class, 'ItemInventoryGroup_id', 'id');
    }
}
