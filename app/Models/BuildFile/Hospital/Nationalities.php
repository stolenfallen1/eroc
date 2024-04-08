<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationalities extends Model
{
    use HasFactory;
    protected $table = 'mscNationality';
    protected $connection = "sqlsrv";
   
    protected $guarded = [];
}
