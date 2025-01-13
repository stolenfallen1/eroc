<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Appointments\AppointmentSlot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Appointments\AppointmentCenterSectection;
use App\Models\BuildFile\FmsExamProcedureItems;

class AppointmentCenter extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'AppointmentCenters';
    protected $guarded = [];

    protected $with = ['sections','procedures'];

    public function sections(){
        return $this->hasMany(AppointmentCenterSectection::class,'appointment_center_id','id');
    }
    public function procedures(){
        
        return $this->hasMany(FmsExamProcedureItems::class,'map_revenue_code','revenueID');
    }


    public function slots(){
        return $this->hasMany(AppointmentSlot::class,'center_id','id');
    }
    
}

