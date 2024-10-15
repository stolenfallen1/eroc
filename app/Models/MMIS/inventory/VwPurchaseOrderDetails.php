<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwPurchaseOrderDetails extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwPurchaseOrderDetails';
    protected $guarded = [];
    
}
