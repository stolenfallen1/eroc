<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAssignedReports extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysUserreports';
    protected $guarded = [];
}
