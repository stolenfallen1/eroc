<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItems extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'RRDetail';

    protected $guarded = [];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'rr_id');
    }

    public function item()
    {
        return $this->belongsTo(Itemmasters::class, 'rr_Detail_Item_Id');
    }
}
