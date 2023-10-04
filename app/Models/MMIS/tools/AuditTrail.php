<?php

namespace App\Models\MMIS\tools;

use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouses;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.inventoryTransaction';
    protected $guarded = [];

    protected $with = ['item', 'warehouse', 'branch', 'createdBy'];

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_Id', 'id');
    }

    public function branch(){
        return $this->belongsTo(Branchs::class, 'branch_Id', 'id');
    }

    public function item(){
        return $this->belongsTo(Itemmasters::class, 'transaction_Item_Id', 'id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class, 'createdBy', 'idnumber');
    }
}
