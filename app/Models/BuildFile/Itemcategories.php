<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itemcategories extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "Itemcategories";

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'item_Category_Id', 'id');
    }
}
