<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORNursePositionModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'mscOperatingRoomNursePositions';
    protected $guarded = [];
}
