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
}
