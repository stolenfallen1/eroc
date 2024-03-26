<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genericnames extends Model
{
    use HasFactory;
    protected $table = 'medGeneric';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
