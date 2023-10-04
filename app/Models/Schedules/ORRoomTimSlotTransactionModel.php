<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORRoomTimSlotTransactionModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomTimeSlots';
    protected $guarded = [];
}
