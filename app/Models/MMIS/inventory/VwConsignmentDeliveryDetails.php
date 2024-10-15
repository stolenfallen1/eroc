<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwConsignmentDeliveryDetails extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwConsignmentDeliveryDetails';
    protected $guarded = [];
    
}
