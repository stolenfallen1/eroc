<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unitofmeasurement extends Model
{
    use HasFactory;
    protected $table = 'mscUnitofmeasurements';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
