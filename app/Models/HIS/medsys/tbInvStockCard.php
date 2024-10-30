<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbInvStockCard extends Model
{
    protected $connection = 'sqlsrv_medsys_inventory';
    protected $table = 'INVENTORY.dbo.tbInvStockCard';
    protected $primaryKey = 'SequenceNumber';
    protected $guarded = [];
    public $timestamps = false;
}
