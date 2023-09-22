<?php

namespace App\Models\MMIS;

use App\Models\MMIS\inventory\Delivery;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;
    protected $table = 'CDG_MMIS.dbo.audits';
    protected $connection = "sqlsrv_mmis";

    protected $guarded = [];

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }
}
