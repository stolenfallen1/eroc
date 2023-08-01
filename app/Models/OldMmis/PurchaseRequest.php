<?php

namespace App\Models\OldMMIS;

use App\Models\OldMMIS\Canvas;
use App\Models\OldMmis\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function purchaseForCanvas(){
        return $this->hasOne(RequestForCanvas::class, 'prnumber', 'prnumber');
    }

    public function canvas(){
        return $this->hasMany(Canvas::class, 'prnumber', 'prnumber');
    }
}
