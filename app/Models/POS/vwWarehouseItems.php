<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ItemBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class vwWarehouseItems extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.vwWarehouseItems';
    protected $guarded = [];
    protected $with = ['item_batch'];


    public function item_batch(){
        return $this->hasMany(ItemBatch::class, 'item_Id', 'id');
    }
}
