<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAppointment extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'AppointmentUser';
    protected $guarded = [];
 
    public $timestamps = false;
}
