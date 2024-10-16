<?php

namespace App\Models\BuildFile\Hospital;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\mscHospitalBldgFloors;

class mscHospitalStation extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscHospitalStation';
    protected $guarded = [];
    // protected $with = ['floors'];

    public function floors(){
        return $this->belongsTo(mscHospitalBldgFloors::class,'mscHospitalBldgFloors_id', 'id');
    }
    
}
