<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\mscHospitalStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\mscHospitalRoomsClass;
use App\Models\BuildFile\Hospital\mscHospitalRoomsStatus;

class mscHospitalRooms extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalRooms';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    protected $with = ['stations','roomStatus','roomClass','stations.floors'];

    public function stations(){
        return $this->belongsTo(mscHospitalStation::class,'station_id', 'id');
    }
    public function roomStatus(){
        return $this->belongsTo(mscHospitalRoomsStatus::class,'room_status_id', 'id');
    }
    public function roomClass(){
        return $this->belongsTo(mscHospitalRoomsClass::class,'room_class_id', 'id');
    }
}
