<?php

namespace App\Models\Sampledb;

use Illuminate\Database\Eloquent\Model;


class Tests extends Model
{
    
    protected $connection = 'sqlsrv_sampledb';
    protected $table = 'tests';
}
