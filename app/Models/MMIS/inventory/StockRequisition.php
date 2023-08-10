<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\ItemGroup;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\BuildFile\Warehouses;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequisition extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.stock_requisitions';
    protected $guarded = [];

    public function items(){
        return $this->hasMany(StockRequisitionItem::class);
    }

    public function inventoryGroup(){
        return $this->belongsTo(ItemGroup::class, 'item_group_id', 'id');
    }

    public function category(){
        return $this->belongsTo(Itemcategories::class, 'category_id', 'id');
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'unit_id', 'id');
    }

    public function requestedBy(){
        return $this->belongsTo(User::class, 'request_by_id', 'idnumber');
    }

    public function receivedBy(){
        return $this->belongsTo(User::class, 'receiver_id', 'idnumber');
    }

    public function transferBy(){
        return $this->belongsTo(User::class, 'transfer_by_id', 'idnumber');
    }

    public function receiveBy(){
        return $this->belongsTo(User::class, 'receive_by_id', 'idnumber');
    }

    public function requesterWarehouse(){
        return $this->belongsTo(Warehouses::class, 'requester_warehouse_id', 'id');
    }

    public function requesterBranch(){
        return $this->belongsTo(Branchs::class, 'requester_branch_id', 'id');
    }

    public function senderWarehouse(){
        return $this->belongsTo(Warehouses::class, 'sender_warehouse_id', 'id');
    }

    public function senderBranch(){
        return $this->belongsTo(Branchs::class, 'sender_branch_id', 'id');
    }

}
