<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'inventoryTransaction';

    protected $guarded = [];
}
