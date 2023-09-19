<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Religions extends Model
{
    use HasFactory;
    protected $table = 'mscReligion_mapping';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
