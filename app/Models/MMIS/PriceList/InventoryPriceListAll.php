<?php

namespace App\Models\MMIS\PriceList;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class InventoryPriceListAll extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.VwInvPriceListAll';
}
