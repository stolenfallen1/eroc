<?php

namespace App\Models\BuildFile\Hospital;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\mscHospitalBldgs;
use App\Models\BuildFile\Hospital\mscHospitalStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalBldgFloors extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'CDG_CORE.dbo.mscHospitalBldgFloors';
    protected $guarded = [];
    protected $with = ['building',"stations"];

    public function stations(){
        return $this->hasMany(mscHospitalStation::class,'mscHospitalBldgFloors_id');
    }

    public function building(){
        return $this->belongsTo(mscHospitalBldgs::class,'mscHospitalBldg_id', 'id');
    }
}
