<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\AppointmentUser;
use DB;
use App\Models\BuildFile\Branchs;
use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\AppointmentCenterSectection;

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
        $query->where('role_id',$type);
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

    
    public function confirmedAppointment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $id = $request->id;
            $details = PatientAppointment::where('id', $id)->update(
                [
                    'status_Id'=>2,
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
            $center = AppointmentCenter::where('isactive',1);
            if(isset($payload['id'])){
                $center->where('id',$payload['id']);
            }
            $center->first();
            $center->updateOrCreate(
                [
                    'title'      => $payload['title'],
                    'revenueID'      => $payload['revenueID'],
                ],
                [
                    'title'         => $payload['title'],
                    'revenueID'     => $payload['revenueID'],
                    'icon'     => $payload['icon'],
                    'isactive'      => 1,
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
                    'section_name'          => $payload['section_name'],
                ],
                [
                    'section_name'  => $payload['section_name'],
                    'isactive'      => 1,
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
