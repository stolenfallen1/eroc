<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStockCard extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'INVENTORY.dbo.tbInvStockCard';
    protected $primaryKey = 'SequenceNumber';
    protected $guarded = [];
    public $timestamps = false;
}
