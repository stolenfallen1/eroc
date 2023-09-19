<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeBracket extends Model
{
    use HasFactory;
    protected $table = 'mscAgeBracket';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
