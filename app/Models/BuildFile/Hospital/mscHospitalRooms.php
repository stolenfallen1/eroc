<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscHospitalRooms extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalRooms';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
