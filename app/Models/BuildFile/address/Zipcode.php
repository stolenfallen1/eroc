<?php

namespace App\Models\BuildFile\address;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\address\Region;
use App\Models\BuildFile\address\Province;
use App\Models\BuildFile\address\Municipality;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Zipcode extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'mscAddressZipcodes';
    protected $guarded = [];
    public function regions(){
        return $this->belongsTo(Region::class, 'region_code', 'region_code');
    }
    public function provinces(){
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }
     
    public function muncipalities(){
        return $this->belongsTo(Municipality::class, 'municipality_code', 'municipality_code');
    }
}
