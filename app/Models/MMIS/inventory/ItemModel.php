<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Warehouses;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemModel extends Model
{
    use HasFactory;

    protected $table = 'CDG_MMIS.dbo.itemModelNumberMaster';
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
        return 'Model no. ' . $this->model_Number . ' -  Qty  ' . ((int)$this->item_Qty - $this->item_Qty_Used). ' -  Serial no. ' . $this->model_SerialNumber;
    }
}
