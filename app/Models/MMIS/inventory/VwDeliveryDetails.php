<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwDeliveryDetails extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwDeliveryDetails';
    protected $guarded = [];
    
}
