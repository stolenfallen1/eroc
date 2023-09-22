<?php

namespace App\Models\Database;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'databases';
    protected $guarded = [];
}
