<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'fmsGuarantors';
    protected $guarded = [];

   
}
