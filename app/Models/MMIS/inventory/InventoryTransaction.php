<?php

namespace App\Models\MMIS\inventory;

use App\Models\HIS\his_functions\NurseLogBook;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryTransaction extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.inventoryTransaction';

    protected $guarded = [];

    public function batch(){
        return $this->belongsTo(ItemBatchModelMaster::class, 'batch_id');
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'transaction_Item_UnitofMeasurement_Id','id');
    }

    // Fetching Purposes
    public function nurse_logbook() {
        return $this->belongsTo(NurseLogBook::class, 'transaction_Item_Id', 'item_Id');
    }

}
