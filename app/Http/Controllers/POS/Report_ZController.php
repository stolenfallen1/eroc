<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Systerminals;

class Report_ZController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  
    
    public function Xreading_per_shiftx(){

        $branchid = Auth()->user()->branch_id;
        $terminalid = Request()->payload['terminalid'];
        $shift_id = Request()->payload['shift_id'];
        $cashier_id = Request()->payload['cashier_id'];
        $closeby = Request()->payload['closeby'];
        $date = Carbon::parse(Request()->payload['date'])->format('Y-m-d');
        $data['terminalid'] =  $terminalid ;
        $data['shift_id'] = $shift_id;
        $data['closeBy'] = $closeby;
        $data['data'] = DB::connection('sqlsrv_pos')->select('EXEC sp_XReport_Summary_Per_Shift ?, ?,?,?', [$terminalid,$cashier_id,$shift_id,$date]);
        $data['zreport'] = DB::connection('sqlsrv_pos')->table('reports_Shift_Summary_Sales_temp')->whereDate('transdate',$date)->whereNotNull('sales_batch_number')->where('shift_id',$shift_id)->where('branchid',$branchid)->where('terminalid',$terminalid)->where('cashier_id',$cashier_id)->first();
        return $this->print_out_layout($data,'XreadingReport');
        // return response()->json($data,200); 
    }
    
    public function Xreading_per_shift(){
        $shift_id = Request()->payload['shift_id'];
        $cashier_id = Request()->payload['cashier_id'];
        $date = Carbon::parse(Request()->payload['date'])->format('Y-m-d');
        $terminalid =  Request()->payload['terminalid'];
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $terminaldetails = Systerminals::where('id',$terminalid)->where('isActive', '1')->first();
        
        $transaction = DB::connection('sqlsrv_pos')->select('EXEC sp_XReport_Summary_Per_Shift ?, ?,?,?', [$terminalid,$cashier_id,$shift_id,$date]);
        $shift_group = [];
       
        $summary = [];
        $total_cash_sales =0;
        $total_creditcard_sales =0;
        $total_debitcard_sales =0;
        $total_sales =0;
        $total_refunds =0;
  
        $sales_invoice_group = [];
        foreach ($transaction as $item) {
            if ($item->statusdesc == 'POS -  Completed Order Sales') {
                if ($item->method == 'Cash') {
                    $total_cash_sales += (float) $item->totalamount;
                }
                if ($item->method == 'Credit Card') {
                    $total_creditcard_sales += (float) $item->totalamount;
                }
                if ($item->method == 'Debit Card') {
                    $total_debitcard_sales += (float) $item->totalamount;
                }
                $total_sales +=(float) $item->totalamount;
                
                $invoice = $item->invnno;
                if (isset($sales_invoice_group[$invoice])) {
                    // If the category_data exists, add the item to the category_data's array
                    $sales_invoice_group[$invoice][] = $item;
                } else {
                // If the category doesn't exist, create a new array for the category and add the item
                    $sales_invoice_group[$invoice] = [$item];
                }
            }
            if ($item->statusdesc == 'Refunds') {
                $total_refunds +=(float) $item->totalamount;
            }
            $shift = $item->shift_description;
            if (isset($shift_group[$shift])) {
                $shift_group[$shift][] = $item;
            } else {
                $shift_group[$shift] = [$item];
            }
        }
        $totalvatexempt =0;
        $totaldiscount =0;
        $totalvat =0;
        $totalvatamount=0;
        $total_cash_transaction = 0;
        $total_credit_transaction = 0;
        $total_debit_transaction = 0;
        $total_total_transaction = 0;
        $startinvoice = '';
        $endinvoice = '';
        foreach ($sales_invoice_group as $key => $invoices) {
            if($invoices[0]->method == 'Cash'){
                $total_cash_transaction++;
            }
            if($invoices[0]->method == 'Credit Card'){
                $total_credit_transaction++;
            }
            if($invoices[0]->method == 'Debit Card'){
                $total_debit_transaction++;
            }
            $total_total_transaction++;
            $totaldiscount += (float) $invoices[0]->discount;
            $totalvatexempt += (float) $invoices[0]->vatexempt;
            $totalvat += (float) $invoices[0]->vatamount;
            $totalvatamount += (float) $invoices[0]->vatexempt +  $invoices[0]->vatamount;

            $startinvoice = $invoices[0]->begInvNo;
            $endinvoice = $invoices[0]->EndInvNo;
        }
       
        $data['data'] = $shift_group;
        $data['terminalid'] = $terminalid;
        $data['startinvoice'] = $startinvoice;
        $data['endinvoice'] = $endinvoice;


        $data['summary_total_cash'] = $total_cash_sales;
        $data['summary_total_debitcard'] = $total_debitcard_sales;
        $data['summary_total_creditcard'] = $total_creditcard_sales;
        $data['summary_total_sales'] = $total_sales;

        $data['summary_vat_exempt'] = $totalvatexempt;
        $data['summary_vat_sales'] = $totalvat;
        $data['summary_total_vat_sales'] = $totalvatamount;

        $data['summary_discount'] = $totaldiscount;
        $data['summary_refund'] = $total_refunds;
        
        $data['summary_cash_transaction'] = $total_cash_transaction;
        $data['summary_credit_transaction'] = $total_credit_transaction;
        $data['summary_debit_transaction'] = $total_debit_transaction;
        $data['summary_sales_transaction'] = $total_total_transaction;
        $data['possetting'] = $possetting;
        $data['terminaldetails'] = $terminaldetails;
        $data['report_date'] =Carbon::parse(Request()->payload['date'])->format('F d, Y');
        $data['zreport'] = DB::connection('sqlsrv_pos')->table('reports_Shift_Summary_Sales_temp')->whereDate('transdate',$date)->whereNotNull('sales_batch_number')->first();
        return $this->print_out_layout($data,'XreadingReport');
        // return response()->json($data,200); 
    }

    public function Zreading_all_shift(){

        $date = Carbon::parse(Request()->payload['date'])->format('Y-m-d');
        $terminalid = Auth()->user()->terminal_id;
        $possetting = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $terminaldetails = Systerminals::where('id',Auth()->user()->terminal_id)->where('isActive', '1')->first();
        
        $transaction = DB::connection('sqlsrv_pos')->select('EXEC sp_ZReport_Summary ?, ?', [$terminalid,$date]);
        $shift_group = [];
       
        $summary = [];
        $total_cash_sales =0;
        $total_creditcard_sales =0;
        $total_debitcard_sales =0;
        $total_sales =0;
        $total_refunds =0;
  
        $total_opening = 0;
        $total_total_closing = 0;


        $sales_invoice_group = [];
        foreach ($transaction as $item) {
            if ($item->statusdesc == 'POS -  Completed Order Sales') {
                

                if ($item->method == 'Cash') {
                    $total_cash_sales += (float) $item->totalamount;
                }
                if ($item->method == 'Credit Card') {
                    $total_creditcard_sales += (float) $item->totalamount;
                }
                if ($item->method == 'Debit Card') {
                    $total_debitcard_sales += (float) $item->totalamount;
                }
                $total_sales +=(float) $item->totalamount;
                
                $invoice = $item->invnno;
                if (isset($sales_invoice_group[$invoice])) {
                    // If the category_data exists, add the item to the category_data's array
                    $sales_invoice_group[$invoice][] = $item;
                } else {
                // If the category doesn't exist, create a new array for the category and add the item
                    $sales_invoice_group[$invoice] = [$item];
                }
            }
            if ($item->statusdesc == 'Refunds') {
                $total_refunds +=(float) $item->totalamount;
            }
            $shift = $item->shift_description;
            if (isset($shift_group[$shift])) {
                $shift_group[$shift][] = $item;
            } else {
                $shift_group[$shift] = [$item];
            }
        }
        $totalvatexempt =0;
        $totaldiscount =0;
        $totalvat =0;
        $totalvatamount=0;
        $total_cash_transaction = 0;
        $total_credit_transaction = 0;
        $total_debit_transaction = 0;
        $total_total_transaction = 0;
        $startinvoice = '';
        $endinvoice = '';

        $sales_temp =  DB::connection('sqlsrv_pos')->table('reports_Shift_Summary_Sales_temp')
        ->where('terminalid',$terminalid)
        ->whereDate('report_date',$date)->get();
       
        foreach($sales_temp as $row){
            $total_opening += (float)$row->opening_amount;
            $total_total_closing += (float) $row->closing_amount;
            // $total_cash_sales += (float) $row->total_cash_sales;
            // $total_creditcard_sales += (float) $row->total_credit_card_sales;
            // $total_debitcard_sales += (float) $row->total_debit_card_sales;
            // $total_sales +=(float) $row->total_cash_sales +  $row->total_credit_card_sales +   $row->total_debit_card_sales;
        }

        foreach ($sales_invoice_group as $key => $invoices) {

            if($invoices[0]->method == 'Cash'){
                $total_cash_transaction++;
            }
            if($invoices[0]->method == 'Credit Card'){
                $total_credit_transaction++;
            }
            if($invoices[0]->method == 'Debit Card'){
                $total_debit_transaction++;
            }

            $total_total_transaction++;
           
            $totaldiscount += (float) $invoices[0]->discount;
            $totalvatexempt += (float) $invoices[0]->vatexempt;
            $totalvat += (float) $invoices[0]->vatamount;
            $totalvatamount += (float) $invoices[0]->vatexempt +  $invoices[0]->vatamount;

            $startinvoice = $invoices[0]->begInvNo;
            $endinvoice = $invoices[0]->EndInvNo;
        }
       
        $data['data'] = $shift_group;
        $data['terminalid'] = $terminalid;
        $data['startinvoice'] = $startinvoice;
        $data['endinvoice'] = $endinvoice;


        $data['summary_total_cash'] = $total_cash_sales;
        $data['summary_total_debitcard'] = $total_debitcard_sales;
        $data['summary_total_creditcard'] = $total_creditcard_sales;
        $data['summary_total_sales'] = $total_sales;

        $data['summary_vat_exempt'] = $totalvatexempt;
        $data['summary_vat_sales'] = $totalvat;
        $data['summary_total_vat_sales'] = $totalvatamount;

        $data['summary_discount'] = $totaldiscount;
        $data['summary_refund'] = $total_refunds;
        
        $data['summary_cash_transaction'] = $total_cash_transaction;
        $data['summary_credit_transaction'] = $total_credit_transaction;
        $data['summary_debit_transaction'] = $total_debit_transaction;
        $data['summary_sales_transaction'] = $total_total_transaction;



        $data['summary_total_opening'] = $total_opening;
        $data['summary_total_closing'] = $total_total_closing;
        $data['summary_sales'] = $total_sales;


        $data['possetting'] = $possetting;
        $data['terminaldetails'] = $terminaldetails;
        $data['report_date'] =Carbon::parse(Request()->payload['date'])->format('F d, Y');
        $data['zreport'] = DB::connection('sqlsrv_pos')->table('reports_Shift_Summary_Sales_temp')->whereDate('transdate',$date)->whereNotNull('sales_batch_number')->first();
        return $this->print_out_layout($data,'ZReadingReport');
        // return response()->json($data,200); 
    }

    public function print_out_layout($data,$name){
        $filename = Auth()->user()->user_ipaddress.'.pdf';
        $data['possetting'] = POSSettings::with('bir_settings')->where('isActive', '1')->first();
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        // $pdf->setPaper('letter', 'portrait');
        $pdf->setPaper(array(0,0,224,650));
        $pdf->loadView('pos_pdf_layout.'.$name, $data)->save(public_path().'/pos_report/'.$name.'_'.$filename);
        $path = url('/pos_report/'.$name.'_'.$filename);
        // $this->ExcelReport($name);
        return response()->json(['pdfUrl' => $path]); 
    }
}
