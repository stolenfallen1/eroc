<?php

namespace App\Models\Schedules;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORPatientModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbmaster'; 
    protected $primaryKey = 'HospNum';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['patient_name'];

    public function getPatientNameAttribute()
    {
        return $this->LastName . ', ' .$this->FirstName. ' ' .$this->MiddleName;
    }
}
