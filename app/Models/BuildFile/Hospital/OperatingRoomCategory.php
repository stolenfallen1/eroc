<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatingRoomCategory extends Model
{
    use HasFactory;
    protected $table = 'mscOperatingRoomCategories';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
