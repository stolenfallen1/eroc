<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\WarehouseSection;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseIssuance extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.expenseIssuances';

    protected $guarded = [];

    public function user(){
        return $this->belongsTo(User::class, 'created_by', 'idnumber');
    }

    public function item(){
        return $this->belongsTo(Itemmasters::class, 'item_id', 'id');
    }

    public function batch(){
        return $this->belongsTo(ItemBatchModelMaster::class, 'batch_id', 'id');
    }

    public function section(){
        return $this->belongsTo(WarehouseSection::class, 'section_id', 'id');
    }

}
