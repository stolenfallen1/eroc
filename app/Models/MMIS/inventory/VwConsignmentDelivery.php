<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\MMIS\inventory\VwConsignmentDeliveryDetails;

class VwConsignmentDelivery extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwConsignmentDelivery';
    protected $guarded = [];
    public function items()
    {
        return $this->hasMany(VwConsignmentDeliveryDetails::class, 'rr_id', 'id');
    }
}
