<?php

namespace App\Models\BuildFile\address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'mscAddressRegions';
    protected $guarded = [];
    public function provinces(){
        return $this->hasMany(Province::class, 'region_code', 'region_code');
    }
}
