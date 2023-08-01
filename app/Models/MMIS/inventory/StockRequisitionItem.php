<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouseitems;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequisitionItem extends Model
{
    use HasFactory;

    public function stockRequisition(){
        return $this->belongsTo(StockRequisition::class);
    }

    public function warehouseItem(){
        return $this->belongsTo(Warehouseitems::class, 'warehouse_item_id', 'id');
    }

    public function item(){
        return $this->belongsTo(Itemmasters::class, 'item_id', 'id');
    }

    public function departmentHeadApprovedBy(){
        return $this->belongsTo(User::class, 'department_head_approved_by', 'idnumber');
    }

    public function departmentHeadDeclinedBy(){
        return $this->belongsTo(User::class, 'department_head_declined_by', 'idnumber');
    }

    public function administratorApprovedBy(){
        return $this->belongsTo(User::class, 'administrator_approved_by', 'idnumber');
    }

    public function administratorDeclinedBy(){
        return $this->belongsTo(User::class, 'administrator_declined_by', 'idnumber');
    }
}
