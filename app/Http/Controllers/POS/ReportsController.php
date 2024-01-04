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

  
    public function print_out_layout($data,$papersize,$name){
        
        $filename = Auth()->user()->user_ipaddress.'.pdf';
        $data['possetting'] = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        if($papersize == '2'){
            $pdf->setPaper(array(0,0,224,650));
        }else{
            $pdf->setPaper('letter', 'portrait');
        }
        
        $pdf->loadView('pos_pdf_layout.'.$name, $data)->save(public_path().'/pos_report/'.$name.'_'.$filename);
        $path = url('/pos_report/'.$name.'_'.$filename);
        
        // $this->ExcelReport($name);
        return response()->json(['pdfUrl' => $path]); 
    }

    public function accountability_report1(Request $request){
        if($request->payload) {
            $user_id = Request()->payload['user_id'] ?? Auth()->user()->idnumber;
            $shift_id = Request()->payload['shift_id'] ?? Auth()->user()->shift;
            $date = Request()->payload['date'] ?? date('Y-m-d');
            $terminal_id = Request()->payload['terminal_id'] ?? Auth()->user()->terminal_id;
            // $data['accountability'] = vwAccountability::whereDate('report_date',$date)->where('shift_code',$shift_id)->where('terminal_id',$terminal_id)->where('user_id',$user_id)->first();
            return response()->json($data,200);
        }
    }
    public function accountability_report(Request $request){
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
           
            $cashonhandreport = OpenningAmount::whereDate('report_date',$date)->where('shift_code',$shift_id)->where('terminal_id',$terminal_id)->where('user_id',$user_id)->first();
            $data['cashonhanddetails'] = $cashonhandreport->filterbyuser()->with('cashonhand_details')->first();
            $data['transdate'] =  Carbon::now()->format('M d, Y');
            return $this->print_out_layout($data,'','Summary');
          
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
