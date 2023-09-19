<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeathType extends Model
{
    use HasFactory;
    protected $table = 'mscDeathTypes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
