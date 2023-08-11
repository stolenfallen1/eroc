<?php

namespace App\Http\Controllers\POS;

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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getcashiername(Request $request){
        $data['cashiername'] ='';
        $data['cashierid'] ='';
        $data['termninalid'] ='';
        if($request->payload){
            $user = User::where('idnumber',$request->payload)->select('firstname','lastname','middlename','idnumber','terminal_id')->first();
            if($user){
                $data['cashiername'] = $user->lastname.', '.$user->firstname.' '.$user->middlename;
                $data['cashierid'] = $user->idnumber;
                $data['termninalid'] = $user->terminal_id;
            }
        }
        return response()->json($data,200);
    }


    public function itemizedReport(Request $request)
    {
        if($request->payload) {
            $date = $request->payload['date'] ?? date('Y-m-d');
            $shift = $request->payload['shift'];
            $cashierid = Request()->payload['cashierid'] ??  Auth()->user()->idnumber;
            $papersize = Request()->payload['print_layout'];
           $terminalid = '1';
            $data['print_layout'] = $papersize;
            // $data['sales_batch_number'] = Request()->payload['sales_batch_number'];
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] =  $terminalid;
            $data['shift'] =  $shift;
            $data['userid'] = $cashierid;
            $data['printdate'] = Carbon::now()->format('m/d/Y');
            $data['printtime'] = Carbon::now()->format('h:i A');
           
            $datatable = DB::connection('sqlsrv_pos')->select('EXEC spItemizedSalesReport ?, ?, ?, ?', [$terminalid, $cashierid, $shift, $date]);
            $data['data'] = [];
            $data['transdate'] =  Carbon::now()->format('M d, Y');
            if($datatable){
                $data['data'] = $datatable;
                $data['transdate'] =Carbon::parse($datatable[0]->transdate)->format('M d, Y') ?? Carbon::parse($datatable[0]->transdate)->format('M d, Y');
            }
            if(Request()->payload['type'] == 'print'){
                if(Request()->payload['report_name'] == 'IR'){
                    return $this->print_out_layout($data,$papersize,'ItemizedReport_v1');
                }
                if(Request()->payload['report_name'] == 'PSR'){
                    return $this->print_out_layout($data,$papersize,'PeriodicSalesReport');
                }
                if(Request()->payload['report_name'] == 'HSR'){
                    return $this->print_out_layout($data,$papersize,'HourlySalesReport');
                }
                if(Request()->payload['report_name'] == 'ZR'){
                    return $this->print_out_layout($data,$papersize,'ZreadingReport');
                }
                if(Request()->payload['report_name'] == 'EJR'){
                    return $this->print_out_layout($data,$papersize,'EjournalReport');
                }
            }
            return response()->json($data,200);
        }

    }
    
    public function itemBatchReport(Request $request)
    {
        if($request->payload) {
            $cashierid = Request()->payload['cashierid'] ??  Auth()->user()->idnumber;;
            $papersize = Request()->payload['print_layout'];
            $sales_batch_number = Request()->payload['sales_batch_number'] ?? '';
            $terminal = (new Terminal)->terminal_details();
            $terminalid = '1';
      
            $data['print_layout'] = $papersize;
            $data['sales_batch_number'] = $sales_batch_number;
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] =  $terminalid ;
            $data['printdate'] = Carbon::now()->format('m/d/Y');
            $data['printtime'] = Carbon::now()->format('h:i A');
            $datatable = DB::connection('sqlsrv_pos')->select('EXEC spBatchSalesReport ?, ?, ?', [$sales_batch_number, $terminalid, $cashierid]);
            $data['data'] =[];
            $data['transdate'] =  Carbon::now()->format('M d, Y');
            if($datatable){
                $data['data'] = $datatable;
                $data['transdate'] =Carbon::parse($datatable[0]->transdate)->format('M d, Y') ?? Carbon::parse($datatable[0]->transdate)->format('M d, Y');
            }
            // Process the results returned by the stored procedure
            if(Request()->payload['type'] == 'print'){
                return $this->print_out_layout($data,$papersize,'ItemBatchReport_v1');
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

    public function itemSummaryReport(Request $request){
      
        if($request->payload) {
           
            $cashierid = Request()->payload['cashierid'] ?? '';
            $cashiername = Auth()->user()->name;
            $papersize = Request()->payload['print_layout'];
            $sales_batch_number = Request()->payload['sales_batch_number'] ?? '';
            $shift = $request->payload['shift'];
           $terminalid = '1';
            $data['print_layout'] = $papersize;
            $data['sales_batch_number'] = $sales_batch_number;
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] =  $terminalid ;
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
                    return $this->print_out_layout($data,$papersize,'summary_report/Summary_thermal');
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
    
    public function BanknoteSummaryReport(Request $request){
        if($request->payload) {
           
            $cashierid = Request()->payload['cashierid'] ?? '';
            $cashiername = Auth()->user()->name;
            $papersize = Request()->payload['print_layout'];
            $sales_batch_number = Request()->payload['sales_batch_number'] ?? '';
            $shift = $request->payload['shift'];
           $terminalid = '1';
            $data['print_layout'] = $papersize;
            $data['sales_batch_number'] = $sales_batch_number;
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] =  $terminalid ;
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
                return $this->print_out_layout($data,$papersize,'Summary');
            }
            return response()->json($data,200);
        }
    }

    public function itemSummaryDetailReport(Request $request){
      
        if($request->payload) {
            $date = $request->payload['date'] ?? date('Y-m-d');
            $shift = $request->payload['shift']['id'] ?? Auth()->user()->shift;
            $papersize = Request()->payload['print_layout'];
            $cashierid = Request()->payload['cashierid'] ??  Auth()->user()->idnumber;;
            $data['print_layout'] = $papersize;
            $data['cashiername'] = Request()->payload['cashiername'] ??  Auth()->user()->lastname;
            $data['date'] = Request()->payload['date'];
            $data['shift'] = Request()->payload['shift'];
            $data['data'] = vwReportsSummarySales::where('isactive', '1')->where('idnumber', $cashierid)->where('shift_id', $shift)->whereDate('transaction_date', $date)
            ->select('customername','sales_invoice_number','payment_total_amount','cashier_name','payment_method','transaction_date','item_name', 'categoryname', 'price', 'itemid','order_item_total_amount',
            DB::raw('SUM(qty) as qty'), 
            DB::raw('SUM(payment_total_amount) as totalamountsales'),
            DB::raw('SUM(payment_vatable_amount) as totalvatamountsales'),
            DB::raw('SUM(payment_vatable_exempt_sales_amount) as totalvatexemptsales'),
            DB::raw('SUM(payment_vatable_sales_amount) as totalvatablesales'),
            DB::raw('SUM(payment_zero_rated_sales_amount) as totalzeroratesales'))
            ->groupBy('sales_invoice_number','customername','payment_total_amount','cashier_name','payment_method','transaction_date','item_name', 'categoryname', 'price', 'itemid','order_item_total_amount')
            ->get();
            if(Request()->payload['type'] == 'print'){
                return $this->print_out_layout($data,$papersize,'SummaryDetails');
            }
            return response()->json($data,200);
        }
    }

    public function ExcelReport($name){
        $filename = $name.'_'.Auth()->user()->user_ipaddress.'.xlsx';
        $data = Array();
        file_put_contents("pos_excel_report/".$filename, $data);
        return Excel::store(new SummaryReport, 'pos_excel_report/'.$filename);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
