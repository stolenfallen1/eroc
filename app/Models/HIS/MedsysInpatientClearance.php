<?php

namespace App\Models\HIS;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\mscHospitalRooms;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysInpatientClearance extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_patient_data_clearances';
    protected $table = 'PATIENT_DATA.dbo.tbpatient';
    protected $guarded = [];
    protected $primaryKey = 'HospNum';

    public function patient_details()
    {
        return $this->belongsTo(MedsysPatientMaster::class, 'HospNum', 'HospNum');
    }
    public function station_details()
    {
        return $this->belongsTo(mscHospitalRooms::class, 'RoomID', 'room_id');
    }
    
}
