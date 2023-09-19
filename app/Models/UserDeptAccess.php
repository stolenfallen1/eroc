<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDeptAccess extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysDeptAccess';
    protected $guarded = [];
    public $timestamps = false;
}
