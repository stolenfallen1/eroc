<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\HemodialysisMonitoringModel;

class HemodialysisMonitoringController extends Controller
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
            $query->with('items','patient_details','doctor_details');
            if (request()->keyword) {
                $query->whereHas('patient_details', function ($query) {
                    $patientName = request()->keyword;
                    $names = explode(',', $patientName); // Split the keyword into firstname and lastname
                    $lastName = $names[0];
                    $firstName = $names[1] ?? '';

                    // Convert the comparison to case-insensitive
                    $startsWithNumber = is_numeric(substr($lastName, 0, 1));

                    if ($lastName != '' && $firstName != '') {
                        // Regular case-insensitive search
                        $query->where('lastname', 'LIKE', $lastName);
                        $query->where('firstname', 'LIKE', ltrim($firstName) . '%');
                    } else {
                        
                        if ($startsWithNumber) {
                            // Handle the case where the first name starts with a number
                            $query->where('pid', 'LIKE', $lastName);
                            $query->orWhere('case_no_out', 'LIKE', $lastName . '%');
                            $query->orWhere('case_no_in', 'LIKE', $lastName . '%');
                        } else {
                            // Regular case-insensitive search
                            $query->where('lastname', 'LIKE', $lastName . '%');
                        }
                    }
                });
            }
            $query->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($query->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
