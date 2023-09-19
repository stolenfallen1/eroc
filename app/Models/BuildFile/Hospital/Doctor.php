<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'hmsDoctors';
    protected $fillable = ['lastname','firstname','middlename', 'doctor_code'];
    protected $appends = ['doctor_name'];

    public function getDoctorNameAttribute()
    {
        return $this->lastname . ', ' .$this->firstname. ' ' .$this->middlename;
    }
}
