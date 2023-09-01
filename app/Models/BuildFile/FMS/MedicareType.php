<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicareType extends Model
{
    use HasFactory;
    protected $table = 'fmsMedicareType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
