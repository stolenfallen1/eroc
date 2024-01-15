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

class HemodialysisDailyCensusController extends Controller
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
    
    public function inpatient()
    {
        try {
            $query = BillingOutModel::query();
            $query->with('patient_registry','patient_registry.patient_details');
            $query->whereHas('patient_registry', function ($q) {
                $q->where('register_source_case_no', '<>', '');
            });
            $query->whereDate('transDate',Carbon::now()->format('Y-m-d'));
            $query->orderBy('transDate', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($query->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function outpatient()
    {
        try {
            $query = BillingOutModel::query();
            $query->with('patient_registry','patient_registry.patient_details');
            $query->whereHas('patient_registry', function ($q) {
                $q->where('register_source_case_no', '=', '');
            });
            $query->whereDate('transDate',Carbon::now()->format('Y-m-d'));
            $query->orderBy('transDate', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($query->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }



    public function inpatient_daily_census_report(){
        
        $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
        $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');

        $query = BillingOutModel::query();
        $query->with('patient_registry', 'patient_registry.patient_details');
        $query->whereHas('patient_registry', function ($q) {
            $q->where('register_source_case_no', '<>', '');
        });
        $query->whereDate('transDate', $startdate);
        $query->orderBy('transDate', 'desc');

        $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d');
        $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');
        $data['startdate'] = $startdate;
        $data['enddate'] =  $enddate;

        $data['results'] = $query->get();
        $data['view_path'] = 'his/report/daily-census/';
        $data['public_path'] = '/his/daily-census-report/';
        return $this->print_out_layout($data, 'inpatient');

    }


    public function outpatient_daily_census_report(){

        $startdate = Carbon::parse(Request()->startdate)->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
        $enddate = Carbon::parse(Request()->enddate)->format('Y-m-d');
        $query = BillingOutModel::query();
        $query->with('patient_registry', 'patient_registry.patient_details');
        $query->whereHas('patient_registry', function ($q) {
            $q->where('register_source_case_no', '=', '');
        });
        $query->whereDate('transDate', $startdate);
        $query->orderBy('transDate', 'desc');
        $data['startdate'] = $startdate;
        $data['enddate'] =  $enddate;
        $data['results'] = $query->get();
        $data['view_path'] = 'his/report/daily-census/';
        $data['public_path'] = '/his/daily-census-report/';
        return $this->print_out_layout($data, 'outpatient');

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
