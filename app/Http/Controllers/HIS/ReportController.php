<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Helpers\HIS\SysGlobalSetting;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $check_is_allow_medsys;
    public function __construct()
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }


    public function AllMontlyReport()
    {
        try {
            if($this->check_is_allow_medsys) {
                $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d');
                $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');
                $type = 'K';
                $data['startdate'] = $startdate;
                $data['enddate'] =  $enddate;
                $result = DB::connection('sqlsrv_medsys_nurse_station')->select('EXEC spGlobal_MonthlyReports_Hemo ?, ?, ?', [$startdate, $enddate, $type]);

                $revenuegroup = array();
                // Iterate through the items using foreach
                foreach ($result as $item) {
                    $revenue = $item->Department;
                    // Check if the category exists in the groupedItems array
                    if (isset($revenuegroup[$revenue])) {
                        // If the category exists, add the item to the category's array
                        $revenuegroup[$revenue][] = $item;
                    } else {
                        // If the category doesn't exist, create a new array for the category and add the item
                        $revenuegroup[$revenue] = array($item);
                    }
                }
                ksort($revenuegroup);
                $data['results'] = $revenuegroup;
                $data['view_path'] = 'his/report/monthly-report/';
                $data['public_path'] = '/his/monthlyreport/';

                return $this->print_out_layout($data, 'all');
            } else {

            }
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function DailyTransactionReport()
    {

        try {
            if($this->check_is_allow_medsys) {
                $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d');
                $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');
                $type = 'CEBU DOCTORS` UNIVERSITY HOSPITAL, INC.';
                $data['startdate'] = $startdate;
                $data['enddate'] =  $enddate;
                $result = DB::connection('sqlsrv_medsys_nurse_station')->select('EXEC spGlobal_DailyTransaction ?, ?, ?', [$startdate, $enddate, $type]);
                $data['results'] = $result;
                $data['view_path'] = 'his/report/daily-transaction/';
                $data['public_path'] = '/his/dailyreport/';
                return $this->print_out_layout($data, 'daily');
            } else {

            }
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }

    public function print_out_layout($data, $name)
    {

        $filename = Auth()->user()->idnumber.'.pdf';
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        $pdf->setPaper('letter', 'landscape');

        $pdf->loadView($data['view_path'].''.$name, $data)->save(public_path().''.$data['public_path'].''.$name.'_'.$filename);
        $path = url($data['public_path'].''.$name.'_'.$filename);
        return response()->json(['pdfUrl' => $path]);
    }


}
