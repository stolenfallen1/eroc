<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Itemcategories extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "invItemCategories";

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'item_Category_Id', 'id');
    }
}
