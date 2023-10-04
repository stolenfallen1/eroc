<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORCaseTypeModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'mscOperatingRoomCaseType';
    protected $guarded = [];
}
