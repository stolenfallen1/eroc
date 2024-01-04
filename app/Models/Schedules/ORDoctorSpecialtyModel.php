<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORDoctorSpecialtyModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'mscOperatingRoomDoctorSpecialty';
    protected $guarded = [];
}
