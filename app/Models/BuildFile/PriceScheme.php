<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceScheme extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "CDG_CORE.dbo.mscPriceSchemes";
    protected $guarded = [];

    public function priceGroups(){
        return $this->belongsTo(PriceGroup::class,'msc_price_group_id','id');
    }
}
