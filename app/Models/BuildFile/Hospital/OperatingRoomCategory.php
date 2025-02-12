<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatingRoomCategory extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'mscOperatingRoomCategories';
    protected $guarded = [];
}
