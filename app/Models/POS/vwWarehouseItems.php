<?php

namespace App\Models\POS;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class vwWarehouseItems extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.vwWarehouseItems';
    protected $guarded = [];
    protected $with = ['item_batch'];

    public function item_batch(){
        return $this->hasMany(ItemBatchModelMaster::class, 'item_Id', 'id')->where('item_Qty','!=',0)->where('isConsumed',0)->where('warehouse_Id', Auth::user()->warehouse_id);
        // ->whereDate('item_Expiry_Date','>',Carbon::now()->format('Y-m-d'))
    }
}
