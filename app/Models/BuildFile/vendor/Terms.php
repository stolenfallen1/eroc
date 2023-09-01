<?php

namespace App\Models\BuildFile\vendor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terms extends Model
{
    use HasFactory;
    protected $table = 'mscSupplierterms';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
