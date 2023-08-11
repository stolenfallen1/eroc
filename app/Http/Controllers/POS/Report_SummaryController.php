<?php

namespace App\Http\Controllers\POS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\POS\OpenningAmount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Helpers\PosSearchFilter\Terminal;

class Report_SummaryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function itemSummaryReport(Request $request){
      
        if($request->payload) {
            $terminal = (new Terminal())->terminal_details();
            $cashierid = Request()->payload['cashierid'] ?? '';
            $cashiername = Auth()->user()->name;
            $papersize = Request()->payload['print_layout'];
            $sales_batch_number = Request()->payload['sales_batch_number'] ?? '';
            $shift = $request->payload['shift'];
            $terminalid = Request()->payload['termninalid'];
            $data['print_layout'] = $papersize;
            $data['sales_batch_number'] = $sales_batch_number;
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] = $terminalid;
            $data['shift'] =  $shift;
            $data['userid'] = $cashierid;
            $data['cashiername'] = $cashiername;
            $data['printdate'] = Carbon::now()->format('m/d/Y');
            $data['printtime'] = Carbon::now()->format('h:i A');
            $cashonhandreport = new OpenningAmount();
            if($shift == '0' && $cashierid != ''){
                $datatable = DB::connection('sqlsrv_pos')->select('EXEC spSummarySalesReport_All_User ?, ?, ?', [$terminalid, $cashierid, Request()->payload['date']]);
            }else if($shift == '0' && $cashierid == ''){
                $datatable = DB::connection('sqlsrv_pos')->select('EXEC spSummarySalesReport_All_shift ?, ?', [$terminalid, Request()->payload['date']]);
            }else if($shift != '0' && $cashierid == ''){
                $datatable = DB::connection('sqlsrv_pos')->select('EXEC spSummarySalesReport_Per_shift ?, ?, ?', [$terminalid, $shift, Request()->payload['date']]);
            }else if($shift != '0' && $cashierid != ''){
                $datatable = DB::connection('sqlsrv_pos')->select('EXEC spSummarySalesReport_Per_User ?, ?, ?, ?', [$terminalid, $cashierid, $shift, Request()->payload['date']]);
            }
            
            $data['cashonhanddetails'] = $cashonhandreport->filterbyuser()->with('cashonhand_details')->first();
            $data['data'] =[];
            $data['transdate'] =  Carbon::now()->format('M d, Y');
            if($datatable){
                $data['data'] = $datatable;
                $data['transdate'] =Carbon::parse($datatable[0]->transdate)->format('M d, Y') ?? Carbon::parse($datatable[0]->transdate)->format('M d, Y');
            }
            // Process the results returned by the stored procedure

            if(Request()->payload['type'] == 'print'){
                if($papersize == '2'){
                    return $this->print_out_layout($data,$papersize,'Summary_thermal');
                }
                if($shift == '0') {
                    if($cashierid != '') {
                        return $this->print_out_layout($data,$papersize,'summary_report/per_user');
                    }else{
                        return $this->print_out_layout($data,$papersize,'summary_report/all_shift');
                    }
                }else{
                    if($cashierid != '') {
                        return $this->print_out_layout($data,$papersize,'summary_report/per_shift');
                    }else{
                        return $this->print_out_layout($data,$papersize,'summary_report/all_user');
                    }
                }
               
            }
            return response()->json($data,200);
        }
    }

    public function print_out_layout($data,$papersize,$name){
        
        $filename = Auth()->user()->user_ipaddress.'.pdf';
        $data['possetting'] = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        if($papersize == '2'){
            $pdf->setPaper(array(0,0,224,650));
        }else{
            $pdf->setPaper('letter', 'landscape');
        }
        
        $pdf->loadView('pos_pdf_layout.'.$name, $data)->save(public_path().'/pos_report/'.$name.'_'.$filename);
        $path = url('/pos_report/'.$name.'_'.$filename);
        
        // $this->ExcelReport($name);
        return response()->json(['pdfUrl' => $path]); 
    }
}
