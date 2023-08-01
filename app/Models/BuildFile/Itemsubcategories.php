<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Itemsubcategories extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "CDG_CORE.dbo.invItemSubCategories";
    protected $with = ['children'];

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'item_SubCategory_Id', 'id');
    }

    public function parent(){
        return $this->belongsTo(Itemsubcategories::class,'parent_id', 'id');
    }

    public function children(){
        return $this->hasMany(Itemsubcategories::class, 'parent_id', 'id');
    }
}
