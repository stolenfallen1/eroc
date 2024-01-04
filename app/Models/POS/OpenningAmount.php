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
    protected $table = 'CashOnHand';
    protected $guarded = [];
    protected $appends = ['display_text'];
    
    public function cashonhand_details(){
        return $this->hasOne(OpenningDetails::class,'cashonhand_id', 'id');
    }

    public function user_details(){
        return $this->hasOne(User::class,'idnumber', 'createdBy');
    }
    public function openby_details(){
        return $this->hasOne(User::class,'idnumber', 'createdBy');
    }
    public function closeby_details(){
        return $this->hasOne(User::class,'idnumber', 'postedby');
    }
    public function user_shift(){
        return $this->hasOne(mscShiftSchedules::class,'shifts_code', 'shift_code');
    }
    
    public function getDisplayTextAttribute()
    {
        if(Auth::user()->role->name == 'Pharmacist'){
            if(Carbon::now()->format('d/m/Y') == Carbon::parse($this->cashonhand_beginning_transaction)->format('d/m/Y') && Auth()->user()->idnumber == $this->user_id && Auth()->user()->shift == $this->shift_code  &&  $this->isposted == '0'){
                // 1 means allow to edit 
                return '1';
            }else{
                return '0';
            }
        }else{
            if(Carbon::now()->format('d/m/Y') == Carbon::parse($this->cashonhand_beginning_transaction)->format('d/m/Y') && Auth()->user()->idnumber == $this->user_id && Auth()->user()->shift == $this->shift_code  &&  $this->isposted == '0'){
                // 1 means allow to edit 
                return '1';
            }else{
                return '0';
            }
        }
    }

    public function filterbyuser(){
        $datetoday = Request()->payload['date'] ?? Carbon::now()->format('Y-m-d');
        $idnumber = Request()->payload['cashierid'] ?? Auth()->user()->idnumber;
        $terminal_id = Request()->payload['termninalid'] ?? Auth()->user()->terminal_id;
        $shift = Request()->payload['shift'] ?? Auth()->user()->shift;
        return $this->where('user_id',$idnumber)
                    ->where('terminal_id',$terminal_id)
                    ->where('shift_code',$shift)
                    ->whereDate('cashonhand_beginning_transaction',$datetoday);
    }
    public function openamount(){

        $datetoday = Request()->payload['date'] ?? Carbon::now()->format('Y-m-d');
        $idnumber = Request()->payload['cashierid'] ?? Auth()->user()->idnumber;
        $terminal_id = Request()->payload['termninalid'] ?? Auth()->user()->terminal_id;
        $shift = Request()->payload['shift'] ?? Auth()->user()->shift;
        return $this->whereNull('report_date')
                    ->where('user_id',$idnumber)
                    ->where('terminal_id',$terminal_id)
                    ->where('shift_code',$shift)
                    ->whereDate('cashonhand_beginning_transaction',$datetoday)
                    ->select('cashonhand_beginning_amount','cashonhand_beginning_transaction','id');
    }

    public function cashonhand(){
        $datetoday = Request()->payload['date'] ?? Carbon::now()->format('Y-m-d');
        $idnumber = Request()->payload['cashierid'] ?? Auth()->user()->idnumber;
        $terminal_id = Request()->payload['termninalid'] ?? Auth()->user()->terminal_id;
        $shift = Request()->payload['shift'] ?? Auth()->user()->shift;
        return $this->where('user_id',$idnumber)
                    ->where('terminal_id',$terminal_id)
                    ->where('shift_code',$shift)
                    ->whereDate('cashonhand_beginning_transaction',$datetoday)
                    ->select('id','cashonhand_beginning_amount');
    }

    public function details(){
        $datetoday = Request()->payload['date'] ?? Carbon::now()->format('Y-m-d');
        $idnumber = Request()->payload['cashierid'] ?? Auth()->user()->idnumber;
        $terminal_id = Request()->payload['termninalid'] ?? Auth()->user()->terminal_id;
        $shift = Request()->payload['shift'] ?? Auth()->user()->shift;
        return $this->where('user_id',$idnumber)
                    ->where('terminal_id',$terminal_id)
                    ->where('shift_code',$shift)
                    ->whereDate('cashonhand_beginning_transaction',$datetoday)
                    ->whereNull('report_date');
    }
}
