<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGlobalAccess extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysUserGlobalAccess';
    protected $guarded = [];
    public $timestamps = false;
}
