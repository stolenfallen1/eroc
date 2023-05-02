<?php

namespace App\Models\POS;

use App\Models\BuildFile\mscShiftSchedules;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpenningAmount extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.CashOnHand';
    protected $guarded = [];
    protected $appends = ['display_text'];
    public function cashonhand_details(){
        return $this->hasOne(OpenningDetails::class,'cashonhand_id', 'id');
    }

    public function user_details(){
        return $this->hasOne(User::class,'id', 'createdBy');
    }
    public function user_shift(){
        return $this->hasOne(mscShiftSchedules::class,'shifts_code', 'shift_code');
    }
    
    public function getDisplayTextAttribute()
    {
        if(Auth::user()->role->name == 'Pharmacist'){
            if(Carbon::now()->format('d/m/Y') == Carbon::parse($this->cashonhand_beginning_transaction)->format('d/m/Y') && Auth()->user()->id == $this->user_id && Auth()->user()->shift == $this->shift_code &&  $this->isposted == '0'){
                // 1 means allow to edit 
                return '1';
            }else{
                return '0';
            }
        }else{
            return '1';
        }
    }
}
