<?php

namespace App\Models\BuildFile\Hospital\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSystem extends Model
{
    use HasFactory;
    protected $table = 'sysSubSystem';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
