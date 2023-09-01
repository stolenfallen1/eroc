<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientRelations extends Model
{
    use HasFactory;
    protected $table = 'mscPatientRelations';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
