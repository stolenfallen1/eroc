<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use HasFactory;
    protected $table = 'sysGlobalSettings';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
