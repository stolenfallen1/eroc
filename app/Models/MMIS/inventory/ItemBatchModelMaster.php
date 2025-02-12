<?php

namespace App\Models\MMIS\inventory;

use Carbon\Carbon;
use App\Models\BuildFile\Warehouses;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\BuildFile\Vendors;
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
    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'vendor_id','id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_id');
    }

    public function getDisplayTextAttribute()
    {
        // return 'Batch no. ' . $this->batch_Number . ' -  Qty  ' . ((int)$this->item_Qty - $this->item_Qty_Used). ' -  Expiry no. ' . Carbon::parse($this->item_Expiry_Date)->toDateString();
        return $this->batch_Number . ' -  Expiry Date :' . Carbon::parse($this->item_Expiry_Date)->format('d/m/Y'). ' -  QTY : '.((float)$this->item_Qty - $this->item_Qty_Used);
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'item_UnitofMeasurement_Id','id');
    }

    
    public function transactions(){
        return $this->hasMany(InventoryTransaction::class, 'batch_id', 'id');
    }
}
