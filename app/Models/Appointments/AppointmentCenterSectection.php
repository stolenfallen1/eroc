<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentCenterSectection extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'AppointmentCenterSection';
    protected $guarded = [];
   
}
