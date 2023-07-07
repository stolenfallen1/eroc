<?php

namespace App\Models\OldMMIS;

use App\Models\OldMmis\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'purchase_requests';
    protected $guarded = [];

    public function itemGroup(){
        return $this->belongsTo(InventoryGroup::class, 'itemgroupid', 'id');
    }

    public function purchaseOrders(){
        return $this->hasMany(PurchaseOrder::class, 'prnumber', 'prnumber');
    }
}
