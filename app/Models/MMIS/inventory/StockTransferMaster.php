<?php

namespace App\Models\MMIS\inventory;

use App\Models\User;
use App\Models\BuildFile\Branchs;
use App\Models\Approver\InvStatus;
use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\inventory\StockTransferMasterDetails;

class StockTransferMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.stockTransfersMaster';
    protected $guarded = [];

    public function stockTransferDetails()
    {
        return $this->hasMany(StockTransferMasterDetails::class, 'stock_transfer_id','id');
    }

    public function warehouseSender()
    {
        return $this->belongsTo(Warehouses::class, 'sender_warehouse_id');
    }

    public function warehouseReceiver()
    {
        return $this->belongsTo(Warehouses::class, 'receiver_warehouse_id');
    }

    public function tranferBy()
    {
        return $this->belongsTo(User::class, 'transfer_by', 'idnumber');
    }


    public function status()
    {
        return $this->belongsTo(InvStatus::class, 'status');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by', 'idnumber');
    }
    public function branch(){
        return $this->belongsTo(Branchs::class, 'branch_id','id');
    }
}
