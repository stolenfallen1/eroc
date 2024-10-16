<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwSchedules extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'VwSchedules';
    protected $guarded = [];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->lastname.', '.$this->firstname.' '.$this->middlename;
    }
}
