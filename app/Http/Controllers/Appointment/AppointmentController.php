<?php

namespace App\Http\Controllers\Appointment;

use DB;
use Carbon\Carbon;
use App\Helpers\SMSHelper;
use Illuminate\Http\Request;
use App\Models\BuildFile\Branchs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointments\VwSchedules;
use App\Models\Appointments\ReservedSlot;
use App\Models\Appointments\AppointmentSlot;
use App\Models\Appointments\AppointmentType;
use App\Models\Appointments\AppointmentUser;
use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\PatientAppointmentCheckIn;
use App\Models\Appointments\AppointmentCenterSectection;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\HIS\MedsysPatientMaster;

class AppointmentController extends Controller
{
   
    public function __construct() {}
 

    public function image(Request $request)
    {
        $id = $request->id;
        $type = $request->type;
        $data['data'] = PatientAppointment::where('id', $id)->first();
        $data['type'] = $type;
        return view('attachedfile.index', $data);
    }

    public function index(Request $request)
    {
        $query = PatientAppointmentsTemporary::query();
        $keyword = $request->keyword;
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('lastname', 'like', '%' . $keyword . '%')->orWhere('firstname', 'like', '%' . $keyword . '%');
            });
        }
       
       
        // Handle branch and tab filtering (if provided)
        $branch = $request->branch;
        $tab = $request->tab;

        // Handle sorting (if provided)
        $sortBy = $request->get('sortBy', 'lastname'); // Default sorting by 'lastname'
        $sortDirection = 'asc';

        // Check if sortBy has a direction (e.g., "middlename/desc")
        if (strpos($sortBy, '/') !== false) {
            [$sortByColumn, $sortDirection] = explode('/', $sortBy);
            $query->orderBy($sortByColumn, $sortDirection);
        } else {
            // Default to ascending if no direction is provided
            $query->orderBy($sortBy, $sortDirection);
        }

        // Handle pagination
        $per_page = $request->get('per_page', 15); // Default to 15 items per page
        $page = $request->get('page', 1); // Default to page 1

        // Return the paginated response
        return response()->json($query->paginate($per_page, ['*'], 'page', $page), 200);
    }

    public function users(Request $request)
    {
        $query = AppointmentUser::query();
        $keyword = $request->keyword;
        $type = $request->type;
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('lastname', 'like', '%' . $keyword . '%')->orWhere('firstname', 'like', '%' . $keyword . '%');
            });
        }
        if(Auth()->guard('patient')->user()->role_id != '5'){
            $query->where('role_id',Auth()->guard('patient')->user()->role_id);
        }else{
            if($type != 2){
                $query->whereIn('role_id',[1,3,5]);
            }else{
                $query->whereIn('role_id',[2]);
            }
        }
        // Handle sorting (if provided)
        $sortBy = $request->get('sortBy', 'lastname'); // Default sorting by 'lastname'
        $sortDirection = 'asc';
        // Check if sortBy has a direction (e.g., "middlename/desc")
        if (strpos($sortBy, '/') !== false) {
            [$sortByColumn, $sortDirection] = explode('/', $sortBy);
            $query->orderBy($sortByColumn, $sortDirection);
        } else {
            // Default to ascending if no direction is provided
            $query->orderBy($sortBy, $sortDirection);
        }

        // Handle pagination
        $per_page = $request->get('per_page', 15); // Default to 15 items per page
        $page = $request->get('page', 1); // Default to page 1

        // Return the paginated response
        return response()->json($query->paginate($per_page, ['*'], 'page', $page), 200);
    }

    
    public function checkedIn(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            
            $id = $request->id;
            $details = PatientAppointment::where('id', $id)->first();

            PatientAppointmentCheckIn::updateOrCreate(
                [
                    'patient_Id'=> $details['patient_Id'],
                    'case_No'=> $details['case_No'],
                ],
                [
                'branch_Id'=> $details['patient']['branch_Id'],
                'appointment_ReferenceNumber'=> $details['appointment_ReferenceNumber'],
                'patient_Id'=> $details['patient_Id'],
                'case_No'=> $details['case_No'],
                'checkin_Time'=>Carbon::now()->format('H:i:s'),
                'checkinby'=> Auth()->guard('patient')->user()->portal_UID,
                'status_Id'=> $details['status_Id'],
                'createdby'=> Auth()->guard('patient')->user()->portal_UID,
                'created_at'=>Carbon::now(),
            ]);

            $details->update(
                [
                    'status_Id'=>3,
                    'updated_at'=>Carbon::now()
                ]
            );
            DB::connection('sqlsrv_patient_data')->commit();
            $data = [
                'patient_name' => $details['patient']['name'],
                'date_schedule' => $details['appointment_Date'],
                'reference_no' => $details['appointment_ReferenceNumber'],
            ];
            // $mobileno = $details['patient']['mobile_Number'];
            // $phoneNumberWithoutLeadingZero = ltrim($mobileno, '0');
            // $helpersms = new SMSHelper();
            // $helpersms->sendSms($phoneNumberWithoutLeadingZero,SMSHelper::checkedIn_message($data));
            return response()->json(['details' => $details], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function transfer(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            
            $id = $request->payload['id'];
            $details = PatientAppointment::where('id', $id)->first();

            $details->update(
                [
                    'appointment_section_id'=>$request->payload['transfer_section_id'],
                    'status_Id'=>isset($request->payload['status_id']) ? $request->payload['status_id'] : 3,
                    'isSmsSend'=>0,
                    'updated_at'=>Carbon::now()
                ]
            );
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json(['details' => $details], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function sendMessage(Request $request){
        $data = [
            'patient_name' => $request->patient_name,
        ];
        $phoneNumberWithoutLeadingZero = ltrim($request->mobileno, '0');

        $helpersms = new SMSHelper();
        $helpersms->sendSms($phoneNumberWithoutLeadingZero,SMSHelper::sendTextMessage($data));
        PatientAppointment::where('id',$request->id)->update(['isSmsSend',1]);

        return response()->json([
            'message' => 'Outpatient data registered successfully',
        ], 200);
    }

    public function storeUser(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {

            $payload = $request->payload;
            $userLogin = AppointmentUser::whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'));
            if(isset($payload['id'])){
                $userLogin->where('id',$payload['id']);
            }
            $userLogin->first();
            $userLogin->updateOrCreate(
                [
                    'lastname'      => $payload['lastname'],
                    'firstname'     => $payload['firstname'],
                ],
                [
                    'lastname'      => $payload['lastname'],
                    'firstname'     => $payload['firstname'],
                    'middlename'    => $payload['middlename'],
                    'birthdate'     => $payload['birthdate'],
                    'name'          => $payload['lastname'] . ', ' . $payload['firstname'] . ' ' . $payload['middlename'],
                    'mobileno'      => $payload['mobileno'],
                    'portal_UID'    => $payload['portal_UID'],
                    'passcode'      => $payload['portal_PWD'],
                    'portal_PWD'    => Hash::make($payload['portal_PWD']),
                    'role_id'       => $payload['role_id'],
                    'branch_id'     => $payload['branch_id'],
                    'center_id'     => $payload['center_id']['id'],
                    'section_id'    => $payload['section_id'],
                    'email'         => $payload['email'],
                    'isactive'      => 1,
                    'created_at'    => Carbon::now(),
                    'isonline'      => 1,
                ]
            );

            DB::connection('sqlsrv')->commit();
            return response()->json(['details' => $userLogin], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function getslots(Request $request){
        $slots = AppointmentSlot::where('isactive',1)->get();
        return response()->json(['data' => $slots]);
    }

    public function getAppointmentType(Request $request){
        $type = AppointmentType::where('isactive',1)->get();
        return response()->json($type,200);
    }
    
    public function reserveSlots(Request $request){
        $reservedSlots = ReservedSlot::with('slot')->get();
        return response()->json(['data' => $reservedSlots]);
    }

    public function saveSlot(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {

            $payload = $request->payload;
            if(isset($payload['id'])){
                AppointmentSlot::where('id',$payload['id'])->update(
                    [
                        'branch_id'             =>1,
                        'isactive'              =>$payload['isactive'],
                        'period'                =>$payload['period'],
                        'center_id'             =>$payload['center_id'],
                        'no'                    =>$payload['no'],
                        'appointment_type'      =>$payload['appointment_type'],
                        'updated_at'            =>Carbon::now(),
                    ]
                );
            }else{
                AppointmentSlot::updateOrCreate(
                    [
                        'center_id'         =>$payload['center_id'],
                        'no'                =>$payload['no'],
                        'appointment_type'  =>$payload['appointment_type'],
                        'period'            =>$payload['period'],
                    ],
                    [
                        'branch_id'             =>1,
                        'isactive'              =>$payload['isactive'],
                        'period'                =>$payload['period'],
                        'center_id'             =>$payload['center_id'],
                        'no'                    =>$payload['no'],
                        'appointment_type'      =>$payload['appointment_type'],
                        'created_at'            =>Carbon::now(),
                    ]
                );
            }
            
            DB::connection('sqlsrv')->commit();
            return response()->json(['details' => 'sucess'], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function saveReserveSlots(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            $slotsQuery = AppointmentSlot::where('isactive',1)->where('id',$payload['slot_id'])->first();
            if(isset($payload['id'])){
                ReservedSlot::where('id',$payload['id'])->update(
                    [
                        'isactive'              =>$payload['isactive'],
                        'center_id'             =>$payload['center_id'],
                        'slot_id'               =>$payload['slot_id'],
                        'slot_no'               =>$slotsQuery->no,
                        'date'                  =>$payload['date'],
                        'updated_at'            =>Carbon::now(),
                    ]
                );
            }else{
                ReservedSlot::updateOrCreate(
                    [
                        'slot_id'               =>$payload['slot_id'],
                        'center_id'             =>$payload['center_id'],
                        'date'                  =>$payload['date'],
                    ],
                    [
                        'isactive'              =>$payload['isactive'],
                        'slot_no'               =>$slotsQuery->no,
                        'center_id'             =>$payload['center_id'],
                        'slot_id'               =>$payload['slot_id'],
                        'date'                  =>$payload['date'],
                        'created_at'            =>Carbon::now(),
                    ]
                );
            }
            
            DB::connection('sqlsrv')->commit();
            return response()->json(['details' => 'sucess'], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }
    
    public function branches()
    {
        $data = Branchs::all();
        return response()->json($data, 200);
    }

    public function centers(Request $request)
    {
        $centers = AppointmentCenter::get();
        return response()->json($centers, 200);
    }

    public function getschedules(Request $request)
    {
        $schedules = VwSchedules::where('isOnline',1);
        if(Auth()->guard('patient')->user()->role_id !== 5){
            $schedules->where('appointment_center_id',Auth()->guard('patient')->user()->center_id)->where('appointment_section_id',Auth()->guard('patient')->user()->section_id);
        }
        
        $data = $schedules->get();
        return response()->json($data, 200);
    }
    


    public function getCenters(Request $request)
    {
        $query = AppointmentCenter::query();
        $keyword = $request->keyword;
        if ($keyword) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        // Handle pagination
        $per_page = $request->get('per_page', 15); // Default to 15 items per page
        $page = $request->get('page', 1); // Default to page 1

        // Return the paginated response
        return response()->json($query->paginate($per_page, ['*'], 'page', $page), 200);
    }

    public function storeCenter(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {

            $payload = $request->payload;
            $center = AppointmentCenter::where('revenueID',$payload['revenueID']);
            if(isset($payload['id'])){
                $center->where('id',$payload['id']);
            }
            $center->first();
            $center->updateOrCreate(
                [
                    'revenueID'      => $payload['revenueID'],
                ],
                [
                    'title'         => $payload['title'],
                    'revenueID'     => $payload['revenueID'],
                    'icon'          => $payload['icon'],
                    'isactive'      => $payload['isactive'] == true ? 1 : 0,
                    'created_at'    => Carbon::now(),
                ]
            );

            DB::connection('sqlsrv')->commit();
            return response()->json(['details' => $center], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function storeSection(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {

            $payload = $request->payload;
            $section = AppointmentCenterSectection::where('isactive',1);
            if(isset($payload['section_id'])){
                $section->where('id',$payload['section_id']);
            }
            $section->first();
            $section->updateOrCreate(
                [
                    'appointment_center_id' => $payload['id'],
                ],
                [
                    'section_name'  => $payload['section_name'],
                    'isactive'      => $payload['isactive'] == true ? 1 : 0,
                    'created_at'    => Carbon::now(),
                ]
            );

            DB::connection('sqlsrv')->commit();
            return response()->json(['details' => $section], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }
    
}
