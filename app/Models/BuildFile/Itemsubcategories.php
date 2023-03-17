<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Itemsubcategories extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "invItemSubCategories";

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'item_SubCategory_Id', 'id');
    }
}
