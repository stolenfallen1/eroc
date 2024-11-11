<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'INVENTORY.dbo.tbinvent';
    protected $guarded = [];
    public $timestamps = false;
}
