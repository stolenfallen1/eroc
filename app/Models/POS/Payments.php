<?php

namespace App\Models\POS;

use Carbon\Carbon;
use DB;
use App\Models\POS\Orders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payments extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_pos';
    protected $table = 'payments';
    protected $guarded = [];
    
    
    public function orders()
    {
        return $this->belongsTo(Orders::class, 'order_id', 'id');
    }

    public function nofilterbyreportdate()
    {
        $shift = DB::connection('sqlsrv')->table('mscShiftSchedules')->where('shifts_code',Auth()->user()->shift)->first();
        $from = Carbon::now()->format('Y-m-d').' '.$shift->beginning_military_hour.':00:00';
        $to = Carbon::now()->format('Y-m-d').' '.$shift->end_military_hour.':59:59';
        return $this->where('createdBy', Auth()->user()->idnumber)
                    ->where('terminal_id',Auth()->user()->terminal_id)
                    ->where('shift_id',Auth()->user()->shift)
                    ->whereBetween('payment_date',[$from,$to])
                    ->selectRaw(
                        '(SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount'
                    );
    }
    public function filterbyreportdate()
    {
        $shift = DB::connection('sqlsrv')->table('mscShiftSchedules')->where('shifts_code',Auth()->user()->shift)->first();
        $from = Carbon::now()->format('Y-m-d').' '.$shift->beginning_military_hour.':00:00';
        $to = Carbon::now()->format('Y-m-d').' '.$shift->end_military_hour.':59:59';
        return $this->where('createdBy', Auth()->user()->idnumber)
                ->where('terminal_id',Auth()->user()->terminal_id)
                ->where('shift_id',Auth()->user()->shift)
                ->whereNull('report_date')->whereBetween('payment_date',[$from,$to])
                ->selectRaw('
                    (SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount'
                );
    }

    public function movementtransation()
    {
        $shift = DB::connection('sqlsrv')->table('mscShiftSchedules')->where('shifts_code',Auth()->user()->shift)->first();
        $from = Carbon::now()->format('Y-m-d').' '.$shift->beginning_military_hour.':00:00';
        $to = Carbon::now()->format('Y-m-d').' '.$shift->end_military_hour.':59:59';
        return $this->where('user_id', Auth()->user()->idnumber)
                    ->where('terminal_id',Auth()->user()->terminal_id)
                    ->where('shift_id',Auth()->user()->shift)
                    ->whereBetween('payment_date',[$from,$to])
                    ->select('id','sales_invoice_number','payment_date','payment_total_amount')
                    ->whereNull('report_date');
    }
}
