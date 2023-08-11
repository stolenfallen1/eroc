<?php

namespace App\Http\Controllers\POS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Helpers\PosSearchFilter\Terminal;

class Report_ItemizedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function itemizedReport(Request $request)
    {
        if($request->payload) {
            $date = $request->payload['date'] ?? date('Y-m-d');
            $shift = $request->payload['shift'];
            $cashierid = Request()->payload['cashierid'] ??  Auth()->user()->idnumber;
            $terminal = (new Terminal)->terminal_details();
      
            $papersize = Request()->payload['print_layout'];
            $terminalid = Request()->payload['termninalid'];
            $data['print_layout'] = $papersize;
            // $data['sales_batch_number'] = Request()->payload['sales_batch_number'];
            $data['date'] = Request()->payload['date'];
            $data['terminalid'] = $terminalid;
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
            return $this->print_out_layout($data,$papersize,'ItemizedReport_v1');
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
