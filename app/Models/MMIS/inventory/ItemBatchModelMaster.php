<?php

namespace App\Models\MMIS\inventory;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemBatchModelMaster extends Model
{
    use HasFactory;

    protected $table = 'CDG_MMIS.dbo.itemBatchModelNumberMaster';
    protected $connection = "sqlsrv_mmis";

    protected $guarded = [];

    protected $appends = ['display_text'];
 
    // protected $fillable = ['batch_Number','display_text','id','item_Expiry_Date','item_Qty','item_Qty_Used'];

    public function item(){
        return $this->belongsTo(Itemmasters::class, 'item_Id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_id');
    }

    public function getDisplayTextAttribute()
    {
        // return 'Batch no. ' . $this->batch_Number . ' -  Qty  ' . ((int)$this->item_Qty - $this->item_Qty_Used). ' -  Expiry no. ' . Carbon::parse($this->item_Expiry_Date)->toDateString();
        return $this->batch_Number . ' -  Expiry Date :' . Carbon::parse($this->item_Expiry_Date)->format('d/m/Y'). ' -  QTY : '.((float)$this->item_Qty - $this->item_Qty_Used);
    }

    public function transactions(){
        return $this->hasMany(InventoryTransaction::class, 'batch_id', 'id');
    }
}
