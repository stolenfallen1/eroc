<?php

namespace App\Models\MMIS\inventory\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RRDetailsModel extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_medsys_inventory";
    protected $table = 'INVENTORY.dbo.tbInvRRDetails';
    protected $primaryKey = 'RecordNumber';
    protected $guarded = [];
    public $timestamps = false;
}
