<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Religions extends Model
{
    use HasFactory;
    protected $table = 'mscReligions';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
