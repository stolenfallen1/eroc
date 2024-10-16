<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscDebitCard extends Model
{
    use HasFactory;
    protected $table = 'mscDebitCards';
    protected $connection = "sqlsrv";
}
