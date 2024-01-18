<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\PatientRegistry;
use App\Http\Controllers\Controller;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\MedsysTBDailyBillModel;
use App\Models\HIS\HemodialysisMonitoringModel;

class HemodialysisDoctorCommissionController extends Controller
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
    
    public function index()
    {
        try {
            $query = HemodialysisMonitoringModel::query();
            $query->with('doctor_details','patient_details');
            $query->where('revenue_id','MD');
            $query->whereDate('transdate',Carbon::now()->format('Y-m-d'));
            $query->orderBy('transdate', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($query->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function commission_report(){
        
        $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
        $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d'). ' 23:59';
        $query = HemodialysisMonitoringModel::query();
        $query->with('doctor_details', 'patient_details');
        $query->where('revenue_id', 'MD');
        $query->whereBetween('transdate', [$startdate,$enddate]);
        $query->orderBy('transdate', 'desc');
        $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d');
        $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');
        $data['startdate'] = $startdate;
        $data['enddate'] =  $enddate;
        $data['results'] = $query->get();
        $data['view_path'] = 'his/report/doctor-commission/';
        $data['public_path'] = '/his/doctor-commission/';
        return $this->print_out_layout($data, 'commission');

    }
    public function print_out_layout($data, $name)
    {

        $filename = Auth()->user()->idnumber.'.pdf';
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isHtml5ParserEnabled' => true, 'enable_remote' => true,
        'tempDir' => public_path(), 'chroot' => public_path('images/logos/newhead.png'), ]);
        $pdf->setPaper('letter', 'portrait');

        $pdf->loadView($data['view_path'].''.$name, $data)->save(public_path().''.$data['public_path'].''.$name.'_'.$filename);
        $path = url($data['public_path'].''.$name.'_'.$filename);
        return response()->json(['pdfUrl' => $path]);
    }
}
