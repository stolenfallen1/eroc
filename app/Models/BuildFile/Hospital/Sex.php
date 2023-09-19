<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sex extends Model
{
    use HasFactory;
    protected $table = 'mscSex';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
