<?php

namespace App\Models\BuildFile\vendor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;
    protected $table = 'mscSupplierlevels';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
