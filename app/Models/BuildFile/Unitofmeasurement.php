<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unitofmeasurement extends Model
{
    use HasFactory;
    protected $table = 'CDG_CORE.dbo.mscUnitofmeasurements';
    protected $connection = "sqlsrv";
}
