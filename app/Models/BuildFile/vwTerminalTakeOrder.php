<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vwTerminalTakeOrder extends Model
{
    use HasFactory;   
    protected $connection = "sqlsrv";
    protected $table = 'CDG_CORE.dbo.vwTerminalTakeOrder';
}
