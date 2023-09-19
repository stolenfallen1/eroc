<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    use HasFactory;
    protected $table = 'mscCivilStatus_mapping';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
