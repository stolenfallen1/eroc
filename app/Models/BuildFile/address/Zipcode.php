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
    protected $appends = ['zip_code_details'];

    public function regions(){
        return $this->belongsTo(Region::class, 'region_code', 'region_code');
    }
    public function provinces(){
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }
     
    public function muncipalities(){
        return $this->belongsTo(Municipality::class, 'municipality_code', 'municipality_code');
    }

    public function municipalities(){
        return $this->belongsTo(Municipality::class, 'municipality_code', 'municipality_code');
    }


    public function getMunicipality(){
        return $this->belongsTo(Municipality::class,'municipality_code','municipality_code');
    }

    public function getProvince(){
        return $this->belongsTo(Province::class,'province_code','province_code');
    }

    public function getZipCodeDetailsAttribute()
    {
      
        $municipality = $this->getMunicipality()->first();
        $province = $this->getProvince()->first();

        $municipalityName = $municipality ? $municipality->municipality_name : 'Unknown Municipality';
        $provinceName = $province ? $province->province_name : 'Unknown Province';
        return $this->zip_code.' - '.$provinceName.', '.$municipalityName;

    }
}
