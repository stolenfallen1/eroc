<?php

namespace App\Models\BuildFile\Hospital;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\mscHospitalBldgFloors;

class mscHospitalBldgs extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscHospitalBldgs';
    protected $guarded = [];

    public function floors(){
        return $this->hasMany(mscHospitalBldgFloors::class,'mscHospitalBldg_id', 'id')->whereNotIn('id',[1]);
    }
}
