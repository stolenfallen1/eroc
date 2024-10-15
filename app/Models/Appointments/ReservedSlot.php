<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Appointments\AppointmentSlot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservedSlot extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'ReservedSlots';
    protected $guarded = [];

    public function slot(){
        return $this->belongsTo(AppointmentSlot::class,'slot_id','id');
    }

}
