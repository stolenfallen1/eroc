<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysSubModule extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "sysSubModule";
}
