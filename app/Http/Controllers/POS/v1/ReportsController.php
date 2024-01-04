<?php

namespace App\Http\Controllers\POS\v1;

use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\SummaryReport;
use App\Models\POS\POSSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\POS\OpenningAmount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

use App\Helpers\PosSearchFilter\Terminal;
use Illuminate\Support\Facades\Storage;
use App\Models\POS\SpReportsSummarySales;
use App\Models\POS\vwReportsSummarySales;
use App\Helpers\PosSearchFilter\UserDetails;

class ReportsController extends Controller
{
    public function accountability_report(Request $request)
    {
        if($request->payload) {
            $user_id = Request()->payload['user_id'] ?? Auth()->user()->idnumber;
            $shift_id = Request()->payload['shift_id'] ?? Auth()->user()->shift;
            $date = Request()->payload['date'] ?? Carbon::now()->format('m/d/Y');
            $terminal_id = Request()->payload['termninalid'] ?? Auth()->user()->terminal_id;
            $preparedby = Request()->payload['cashierid'] ?? Auth()->user()->name;

            $data['print_layout'] = '';
            $data['date'] = $date;
            $data['terminalid'] =  $terminal_id;
            $data['shift'] =  $shift_id;
            $data['userid'] = $user_id;
            $data['preparedby'] = $preparedby;
            $data['printdate'] = Carbon::now()->format('m/d/Y');
            $data['printtime'] = Carbon::now()->format('h:i A');

            $cashonhandreport = OpenningAmount::whereDate('report_date', $date)->where('shift_code', $shift_id)->where('terminal_id', $terminal_id)->where('user_id', $user_id)->first();
            $data['cashonhanddetails'] = $cashonhandreport->filterbyuser()->with('cashonhand_details')->first();
            $data['transdate'] =  Carbon::now()->format('M d, Y');
            return $this->print_out_layout($data, '', 'Summary');

        }
    }

    public function print_out_layout($data, $papersize, $name)
    {
        $filename = Auth()->user()->user_ipaddress . '.pdf';
        $data['possetting'] = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        if($papersize == '2') {
            $pdf->setPaper(array(0,0,224,650));
        } else {
            $pdf->setPaper('letter', 'portrait');
        }
        $pdf->loadView('pos_pdf_layout.' . $name, $data)->save(public_path() . '/pos_report/' . $name . '_' . $filename);
        $path = url('/pos_report/' . $name . '_' . $filename);
        // $this->ExcelReport($name);
        return response()->json(['pdfUrl' => $path]);
    }
}
