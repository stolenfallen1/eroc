<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouseitemsborroweds extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'warehouseitemsborroweds';
    protected $guarded = [];
}
