<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountClass extends Model
{
    use HasFactory;  
    protected $table = 'fmsAccountClass';
    protected $connection = "sqlsrv";
    protected $guarded = [];

}
