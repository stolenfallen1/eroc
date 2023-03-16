<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antibioticclass extends Model
{
    use HasFactory;
    protected $table = 'mscAntibioticclass';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
