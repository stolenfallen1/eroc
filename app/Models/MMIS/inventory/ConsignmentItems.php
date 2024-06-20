<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\Consignment;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsignmentItems extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.RRDetailConsignment';

    protected $guarded = [];

    protected $appends = ['total_qty'];


    public function getTotalQtyAttribute(){
        return (float)$this->rr_Detail_Item_Qty_Received - (float)$this->pr_item_qty;
    }
    public function batchs()
    {
        return $this->hasMany(ItemBatchModelMaster::class, 'delivery_item_id', 'id')->where('isconsignment',1);
    }

    public function delivery()
    {
        return $this->belongsTo(Consignment::class, 'rr_id');
    }

    public function item()
    {
        return $this->belongsTo(Itemmasters::class, 'rr_Detail_Item_Id');
    }

    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'rr_Detail_Item_UnitofMeasurement_Id_Received');
    }
}
