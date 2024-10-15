<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "invItemManufacturers";
    protected $guarded = [];
}
