<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Warehouses;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequisition extends Model
{
    use HasFactory;

    public function items(){
        return $this->hasMany(StockRequisitionItem::class);
    }

    public function transferBy(){
        return $this->belongsTo(User::class, 'transfer_by_id', 'idnumber');
    }

    public function receiveBy(){
        return $this->belongsTo(User::class, 'receive_by_id', 'idnumber');
    }

    public function senderWarehouse(){
        return $this->belongsTo(Warehouses::class, 'sender_warehouse_id', 'id');
    }

    public function receiverWarehouse(){
        return $this->belongsTo(Warehouses::class, 'receiver_warehouse_id', 'id');
    }
}
