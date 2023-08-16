<?php

namespace App\Models\MMIS\inventory;

use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Warehouses;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'CDG_MMIS.dbo.stockTransfer';

    protected $guarded = [];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function warehouseSender()
    {
        return $this->belongsTo(Warehouses::class, 'sender_warehouse');
    }

    public function warehouseReceiver()
    {
        return $this->belongsTo(Warehouses::class, 'receiver_warehouse');
    }

    public function tranferBy()
    {
        return $this->belongsTo(User::class, 'transfer_by', 'idnumber');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(purchaseOrderMaster::class, 'po_id');
    }

    public function status()
    {
        return $this->belongsTo(InvStatus::class, 'status');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by', 'idnumber');
    }
}
