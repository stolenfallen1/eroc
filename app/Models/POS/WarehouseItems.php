<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItems extends Model
{
    use HasFactory;
    protected $table = 'CDG_MMIS.dbo.vwWarehouseitems';
    protected $connection = "sqlsrv_mmis";
}
