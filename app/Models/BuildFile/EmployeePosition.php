<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePosition extends Model
{
    use HasFactory;
    protected $table = 'mscEmployeePositions';
    protected $connection = "sqlsrv";
    protected $guarded = [];
   
}
