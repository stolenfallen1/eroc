<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;


class Systemuseraccess extends Model
{
    
    protected $connection = "sqlsrv";
    protected $table = 'systemuseraccess';
}
