<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Itemsubcategories extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "invItemSubCategories";
    protected $with = ['children'];
    protected $guarded = [];

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'item_SubCategory_Id', 'id');
    }

    public function parent(){
        return $this->belongsTo(Itemsubcategories::class,'parent_id', 'id');
    }

    public function children(){
        return $this->hasMany(Itemsubcategories::class, 'parent_id', 'id');
    }

    public function categories(){
        return $this->belongsTo(Itemcategories::class, 'category_id', 'id');
    }
     public function update_node(){
        return $this->node_level = $this->id;
    }

}
