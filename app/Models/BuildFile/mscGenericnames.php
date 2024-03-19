<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscGenericnames extends Model
{
    use HasFactory;
    protected $table = 'mscGenericnames';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
