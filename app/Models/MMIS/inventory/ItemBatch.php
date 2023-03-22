<?php

namespace App\Models\MMIS\inventory;

use Carbon\Carbon;
use App\Models\BuildFile\Warehouses;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemBatch extends Model
{
    use HasFactory;

    protected $table = 'CDG_MMIS.dbo.itemBatchNumberMaster';
    protected $connection = "sqlsrv_mmis";

    protected $guarded = [];

    protected $appends = ['display_text'];

    public function item(){
        return $this->belongsTo(Itemmasters::class, 'item_Id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_id');
    }

    public function getDisplayTextAttribute()
    {
        return $this->batch_Number . ' - ' . Carbon::parse($this->item_Expiry_Date)->toDateString() . ' - ' . $this->item_Qty;
    }

}
