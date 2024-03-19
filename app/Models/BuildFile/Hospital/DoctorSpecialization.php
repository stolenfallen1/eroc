<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSpecialization extends Model
{
    use HasFactory;
    protected $table = 'mscDoctorSpecializations';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
