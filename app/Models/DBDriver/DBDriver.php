<?php

namespace App\Models\DBDriver;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DBDriver extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'databases';
}
