<?php

namespace App\Models\TestBD;

use Illuminate\Database\Eloquent\Model;


class Patients extends Model
{
    protected $connection = 'sqlsrv_sampletest';
    protected $table = 'patients';
}
