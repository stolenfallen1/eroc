<?php

namespace App\Models\BuildFile\address;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\address\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Province extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'mscAddressProvinces';
    protected $guarded = [];

    public function regions(){
        return $this->belongsTo(Region::class, 'region_code', 'region_code');
    }
}
