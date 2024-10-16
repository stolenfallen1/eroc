<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\HIS\MedsysInpatient;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\MedsysInpatientClearance;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clearance extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_medsys_patient_data_clearances';
    protected $table = 'PATIENT_DATA.dbo.tbmaster'; 
    protected $primaryKey = 'HospNum';
    protected $fillable = ['HospNum','LastName','FirstName','MiddleName'];
    public $timestamps = false;
    protected $appends = ['patient_name'];
    protected $with = ['patient_details'];

    public function getPatientNameAttribute()
    {
        return $this->LastName . ', ' .$this->FirstName. ' ' .$this->MiddleName;
    }
    
    public function getBirthdateAttribute()
    {
        return Carbon::parse($this->BirthDate)->format('Y-m-d');
    }

    public function patient_details()
    {
        return $this->hasOne(MedsysInpatientClearance::class, 'HospNum', 'HospNum')->select('HospNum','IdNum','RoomID as RoomNo');
    }

}
