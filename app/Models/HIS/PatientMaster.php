<?php

namespace App\Models\HIS;

use App\Models\BuildFile\Hospital\CivilStatus;
use App\Models\BuildFile\Hospital\Nationalities;
use App\Models\BuildFile\Hospital\Religions;
use App\Models\BuildFile\Hospital\Sex;
use App\Models\BuildFile\Hospital\Suffix;
use App\Models\BuildFile\Hospital\Titles;
use Carbon\Carbon;
use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientMaster';
    protected $guarded = [];
    protected $with = ['sex','civilstatus','nationality','religion','suffix','tittles','patient_registry_details'];
    protected $appends = ['patient_name'];
    
    public function getPatientNameAttribute()
    {
        return $this->lastname . ', ' .$this->firstname. ' ' .$this->middlename;
    }
    public function patient_new_registry(){
        return $this->belongsTo(PatientRegistry::class,'patient_id', 'patient_id');
    }
    public function patient_registry_details(){
        return $this->belongsTo(PatientRegistry::class,'patient_id', 'patient_id')->whereDate('registry_date', Carbon::now()->format('Y-m-d'));
    }
    public function patient_registry(){
        return $this->belongsTo(PatientRegistry::class,'patient_id', 'patient_id');
    }

    public function sex(){
        return $this->belongsTo(Sex::class,'sex_id', 'id');
    }

    public function civilstatus(){
        return $this->belongsTo(CivilStatus::class,'civilstatus_id', 'id');
    }

    public function nationality(){
        return $this->belongsTo(Nationalities::class,'nationality_id', 'id');
    }

    public function religion(){
        return $this->belongsTo(Religions::class,'religion_id', 'id');
    }

    public function suffix(){
        return $this->belongsTo(Suffix::class,'suffix_id', 'id');
    }

    public function tittles(){
        return $this->belongsTo(Titles::class,'title_id', 'id');
    }
}
