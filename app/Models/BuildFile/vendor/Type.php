<?php

namespace App\Models\BuildFile\vendor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;
    protected $table = 'mscSuppliertypes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
