<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model
{
    use HasFactory;
    protected $table = 'fmsGLAccountGroup';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
