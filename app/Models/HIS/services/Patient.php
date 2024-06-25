<?php

namespace App\Models\HIS\services;

use App\Models\BuildFile\address\Barangay;
use App\Models\BuildFile\address\Country;
use App\Models\BuildFile\address\Municipality;
use App\Models\BuildFile\address\Province;
use App\Models\BuildFile\address\Region;
use App\Models\BuildFile\address\Zipcode;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Hospital\BloodType;
use App\Models\BuildFile\Hospital\CivilStatus;
use App\Models\BuildFile\Hospital\DeathType;
use App\Models\BuildFile\Hospital\Nationalities;
use App\Models\BuildFile\Hospital\Religions;
use App\Models\BuildFile\Hospital\Sex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientMaster';
    protected $connection = "sqlsrv_patient_data";
    protected $guarded = [];

    // Relationships
    public function patientRegistry(){
        return $this->belongsTo(PatientRegistry::class, 'patient_id', 'patient_id');
    }
    public function sex() {
        return $this->belongsTo(Sex::class, 'sex_id', 'id');
    }
    public function nationality() {
        return $this->belongsTo(Nationalities::class, 'nationality_id' , 'id');
    }
    public function religion() {
        return $this->belongsTo(Religions::class, 'religion_id', 'id');
    }
    public function civilStatus() {
        return $this->belongsTo(CivilStatus::class, 'civilstatus_id', 'id');
    }
    public function deathType() {
        return $this->belongsTo(DeathType::class, 'typeofdeath_id', 'id');
    }
    public function bloodType() {
        return $this->belongsTo(BloodType::class, 'bloodtype_id', 'id');
    }
    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }
    public function provinces() {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }
    public function municipality() {
        return $this->belongsTo(Municipality::class, 'municipality_id', 'id');
    }
    public function barangay() {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'id');
    }
    public function zipcode() {
        return $this->belongsTo(Zipcode::class, 'zipcode_id', 'id');
    }
    public function countries() {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function branch() {
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }
}
