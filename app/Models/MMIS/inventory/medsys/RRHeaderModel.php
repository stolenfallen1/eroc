<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\medsys\RRDetailsModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\inventory\medsys\InventoryStockCard;

class RRHeaderModel extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'INVENTORY.dbo.tbInvRRHeader';

    protected $primaryKey = 'RecordNumber';
    protected $guarded = [];
    public $timestamps = false;

    
    public function details(){
        return $this->hasMany(RRDetailsModel::class, 'RecordNumber');
    }

    public function stockCard(){
        return $this->hasMany(InventoryStockCard::class, 'RRecordNumber');
    }
    
}
