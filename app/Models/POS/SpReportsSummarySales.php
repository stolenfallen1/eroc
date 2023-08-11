<?php

namespace App\Models\POS;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpReportsSummarySales extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'reports_Shift_Summary_Sales_temp';
  
    public function Generate_Shift_Sales()
    {
        $result = DB::connection('sqlsrv_pos')->update("EXEC spGenerate_Shift_Sales ?, ?, ?,?,?",[
            Auth()->user()->branch_id, Auth()->user()->terminal_id, Auth()->user()->shift, Carbon::now()->format('m/d/Y'), Auth()->user()->idnumber,
        ]);
        return $result;
    
    }

    public function summaryfilterbyreportdate(){
        $shift = DB::connection('sqlsrv')->table('mscShiftSchedules')->where('shifts_code',Auth()->user()->shift)->first();
        $from = Carbon::now()->format('Y-m-d').' '.$shift->beginning_military_hour.':00:00';
        $to = Carbon::now()->format('Y-m-d').' '.$shift->end_military_hour.':59:59';
       return $this->whereBetween('transdate',[$from,$to])->where('cashier_id',Auth()->user()->idnumber)->where('terminalid',Auth()->user()->terminal_id)->where('shift_id',Auth()->user()->shift)->whereNull('report_date');
    }

    public function BatchSalesReport($batachno, $terminalid, $userid)
    {
        $result = $this->getConnection()->select('EXEC spBatchSalesReport(?,?,?)', [
            $batachno,
            $terminalid,
            $userid
        ]);
        return $result;
    }
    
}
