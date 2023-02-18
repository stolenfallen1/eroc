<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = "Itemcategories";

    public function purchase_request(){
        return $this->hasMany(PurchaseRequest::class,'categoryid', 'id');
    }
}
