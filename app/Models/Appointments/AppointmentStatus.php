<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentStatus extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'AppointmentStatus';
    protected $guarded = [];
   
}
