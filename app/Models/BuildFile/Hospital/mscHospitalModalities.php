<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalModalities extends Model
{
    use HasFactory;
    protected $table = 'mscModalities';
    protected $connection = "sqlsrv";
    protected $guarded = [];
   
}
