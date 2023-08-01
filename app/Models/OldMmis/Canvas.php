<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Canvas extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'supplier_canvas';
    protected $guarded = [];

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'prnumber', 'prnumber');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class, 'supplier', 'id');
    }
}
