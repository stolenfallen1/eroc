<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\GetIP;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use App\Helpers\HIS\SeriesNo;

use App\Models\HIS\PatientMaster;
use App\Helpers\HIS\MedsysPatient;
use App\Models\HIS\MedsysSeriesNo;
use App\Models\HIS\MedsysGuarantor;
use App\Models\HIS\PatientRegistry;
use App\Helpers\HIS\Medsys_SeriesNo;
use App\Http\Controllers\Controller;
use App\Models\HIS\MedsysOutpatient;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\MedsysHemoPatient;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\MedsysPatientAllergies;
use App\Models\HIS\MedsysPatientInformant;
use App\Models\HIS\MedsysPatientOPDHistory;
use App\Models\HIS\MedsysDoctorConsultation;
use App\Models\HIS\MedsysPatientMasterDetails;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class MasterPatientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $department;
    protected $check_is_allow_medsys;

    public function __construct()
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->department = Auth()->user();
    }

    public function index()
    {
        try {
            if($this->check_is_allow_medsys) {
                $data = PatientMaster::query();
                // $data = MedsysPatientMaster::query();
            } else {
                $data = PatientMaster::query();
            }
            
            if(!is_numeric(Request()->keyword)) {
                $patientname = Request()->keyword ?? '';
                $names = explode(',', $patientname); // Split the keyword into firstname and lastname
                $last_name = $names[0];
                $first_name = $names[1]  ?? '';
                if($last_name != '' && $first_name != '') {
                    $data->where('lastname', $last_name);
                    $data->where('firstname', 'LIKE', '' . ltrim($first_name) . '%');
                } else {
                    $data->where('lastname', 'LIKE', '' . $last_name . '%');
                }
            } else {
                $data->where('patient_id', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

     public function list()
    {
        try {
            $query = PatientMaster::orderBy('id','desc');
            if(Request()->lastname) {
                $query->where('lastname', 'LIKE', '' . Request()->lastname . '%');
            }
            if(Request()->firstname) {
                $query->where('firstname', 'LIKE', '' . Request()->firstname . '%');
            }
            if(Request()->middlename) {
                $query->where('middlename', 'LIKE', '' . Request()->middlename . '%');
            }
            if(Request()->birthdate) {
                $query->whereDate('birthdate', carbon::parse(Request()->birthdate)->format('Y-m-d'));
            }
            $data = $query->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

}
