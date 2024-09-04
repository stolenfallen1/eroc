<?php

namespace App\Models\HIS;

use App\Models\BuildFile\Hospital\mscHospitalStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HISHospitalRooms extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalRooms';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    public function stations() {
        return $this->belongsTo(mscHospitalStation::class,'station_id', 'station_code');
    }
}