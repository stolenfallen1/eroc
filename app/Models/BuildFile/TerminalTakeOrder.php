<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalTakeOrder extends Model
{
    use HasFactory;   
    protected $connection = "sqlsrv";
    protected $table = 'sysTerminalTakeOrder';
    protected $guarded = [];
}
