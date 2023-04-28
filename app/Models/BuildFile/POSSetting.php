<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POSSetting extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_pos";
    protected $table = 'possettings';
    protected $guarded = [];
}
