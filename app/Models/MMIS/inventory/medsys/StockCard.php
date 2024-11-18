<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCard extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'CDG_DB.dbo.tbInvStockCard';
    protected $primaryKey = 'SequenceNumber';
    protected $guarded = [];
}
