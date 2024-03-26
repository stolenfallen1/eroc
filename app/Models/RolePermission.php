<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;   
    protected $connection = 'sqlsrv';
    protected $table = 'permission_role';
    protected $guarded = [];
    
    public $timestamps = false;
}
