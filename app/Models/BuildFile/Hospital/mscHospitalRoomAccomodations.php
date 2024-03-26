<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\mscHospitalStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalRoomAccomodations extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalRoomAccomodations';
    protected $connection = "sqlsrv";
    protected $guarded = [];
   
}
