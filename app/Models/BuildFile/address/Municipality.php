<?php

namespace App\Models\BuildFile\address;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\address\Region;
use App\Models\BuildFile\address\Province;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipality extends Model
{
    use HasFactory;    
    protected $connection = "sqlsrv";
    protected $table = 'mscAddressMunicipalities';
    protected $guarded = [];

    public function regions(){
        return $this->belongsTo(Region::class, 'region_code', 'region_code');
    }
    
    public function provinces(){
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }
}
