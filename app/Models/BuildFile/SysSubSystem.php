<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysSubSystem extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "sysSubSystem";
}
