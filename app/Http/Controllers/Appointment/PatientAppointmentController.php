<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\BuildFile\FmsProcedures;
use App\Models\BuildFile\SystemSequence;
use App\Models\Appointments\PatientAppointment;
use App\Models\BuildFile\address\Zipcode;
use App\Models\BuildFile\address\Barangay;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\AppointmentUser;
use App\Models\BuildFile\Hospital\Nationalities;
use App\Models\BuildFile\Hospital\CivilStatus;
use DB;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Support\Facades\Auth;

use App\Helpers\SMSHelper;
class PatientAppointmentController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        // If a keyword is provided, filter by patient name
        $keyword = $request->keyword;
        $tabType = $request->type;
        $userId = $request->id; // Assuming you are passing the user ID in the request
        $query = PatientAppointment::with('patient'); // Eager load the patient relationship

        if ($userId) {
            // Filter appointments by the authenticated patient ID
            $query->whereHas('patient', function ($q) use ($userId) {
                $q->where('id', $userId);
            });
        }
        if ($keyword) {
            $query->whereHas('patient', function ($q) use ($keyword) {
                $q->where('lastname', 'like', '%' . $keyword . '%')
                    ->orWhere('firstname', 'like', '%' . $keyword . '%');
            });
        }
        if($tabType){
            $query->where('status_Id',$tabType);
        }
        // Handle pagination
        $per_page = $request->get('per_page', 15); // Default to 15 items per page
        $page = $request->get('page', 1); // Default to page 1

        // Return the paginated response
        return response()->json($query->paginate($per_page, ['*'], 'page', $page), 200);
    }

    public function registration(Request $request)
    {
        
        $payload = $request->payload;
        $userLogin = AppointmentUser::whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->updateOrCreate(
            [
                'lastname'      => $payload['lastName'],
                'firstname'     => $payload['firstName'],
            ],
            [
                'lastname'      => $payload['lastName'],
                'firstname'     => $payload['firstName'],
                'middlename'    => $payload['middleName'],
                'birthdate'     => $payload['birthdate'],
                'name'          => $payload['lastName'] . ', ' . $payload['firstName'] . ' ' . $payload['middleName'],
                'mobileno'      => $payload['contactNumber'],
                'portal_UID'    => $payload['portal_UID'],
                'passcode'      => $payload['portal_PWD'],
                'portal_PWD'    => Hash::make($payload['portal_PWD']),
                'role_id'       => 4,
                'branch_id'     => 1,
                'email'         => $payload['email'],
                'isactive'      => 1,
                'created_at'    => Carbon::now(),
                'isonline'      => 1,
            ]
        );
        $patientLogin = PatientAppointmentsTemporary::whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->updateOrCreate(
            [
                'lastname'      => $payload['lastName'],
                'firstname'     => $payload['firstName'],
            ],
            [
                'lastname'          => $payload['lastName'],
                'firstname'         => $payload['firstName'],
                'middlename'        => $payload['middleName'],
                'email_Address'     => $payload['email'],
                'birthdate'         => $payload['birthdate'],
                'branch_Id'         => 1,
                'user_id'           => $userLogin->id,
                'suffix'            => $payload['suffix'],
                'sex_Id'            => $payload['gender'],
                'birthplace'        => $payload['birthPlace'],
                'age'               => $payload['age'],
                'region_Id'         => $payload['region_code'],
                'bldgstreet'        => $payload['currentAddress'],
                'province_Id'       => $payload['province_id'],
                'municiplaity_Id'   => $payload['municipality_id'],
                'barangay_Id'       => $payload['barangay'],
                'zipcode_Id'        => $payload['zipcode']['id'],
                'mobile_Number'     => $payload['contactNumber'],
                'civil_Status_Id'   => $payload['civilStatus'],
                'nationality_Id'    => $payload['nationality'],
                'portal_UID'        => $payload['portal_UID'],
                'portal_PWD'        => Hash::make($payload['portal_PWD']),
            ]
        );


        $token = $userLogin->createToken();
        // Return patient data or a token
        return response()->json(['details' => $userLogin, 'access_token' => $token], 200);
    }



    public function procedures(Request $request)
    {
        $procedures = FmsProcedures::query();
        $procedures->select('pid', 'item_id', 'revenueid', 'revenuecode', 'description', 'price', 'exam_description', 'id', 'transaction_code');
        if ($request->revenueid) {
            $procedures->where('revenuecode', $request->revenueid);
        }
        if ($request->section) {
            $procedures->where('exam_section', $request->section);
        }
        if ($request->filter) {
            $procedures->where('description', 'LIKE', '%' . $request->filter . '%');
        }
        $page  = $request->per_page ?? '50';
        return response()->json($procedures->paginate($page), 200);
    }

    public function doctors(Request $request)
    {
        try {
            $data = Doctor::select('id', 'doctor_code', 'lastname', 'firstname', 'middlename')->where('isactive', 1)->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function updateSlotsAvailability(&$slots, $selectedSlots)
    {
        foreach ($slots as &$slot) {
            foreach ($selectedSlots as $selected) {
                if ($slot['id'] === $selected['id']) {
                    $slot['available'] = false;
                    break; // No need to check further if a match is found
                }
            }
        }

        // Return the updated slots array
        return $slots;
    }

    public function submitpayment(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {

            $sequence = SystemSequence::select('seq_no','digit')->where(['isActive' => true, 'code' => 'APN'])->first();
            $refno = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $patientID = $request->selectedPatientID;
            $slotNo = $request->selectedSlot;
            $date = $request->selectedDate;
            $doctorId = $request->selectedDoctor;
            $sectionID = $request->selectedSectionID;
            $centerID = $request->selectedCenter;
            $mobileno = $request->mobileno;
            $patient_name = $request->patient_name;
            $PaymentTransactionNo = $request->selectedPaymentTransactionNo;
            $totalAmount = $request->selectedTotalAmount;
            
            $procedures = json_decode($request->selectedProcedures, true);

            // Initialize variable to store the path of the uploaded file
            $doctorsFile = '';

            // Check if a file was uploaded
            if ($request->hasFile('selectedDoctorAttachment')) {
                $file = $request->file('selectedDoctorAttachment');

                // Validate the file (optional, but recommended)
                $request->validate([
                    'selectedDoctorAttachment' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation
                ]);

                // Check if the file is valid
                if ($file->isValid()) {
                    // Generate a unique filename
                    $filename = time() . '_' . $file->getClientOriginalName();
                    // Store the file in the 'public/doctors' directory
                    $path = $file->storeAs('doctors', $filename, 'public');
                    $doctorsFile = $path;
                } else {
                    return response()->json(['error' => 'Invalid file upload'], 400);
                }
            } else {
                return response()->json(['error' => 'No file was uploaded'], 400);
            }

            $patieintAppointment = PatientAppointment::whereDate('appointment_Date', $date)->updateOrCreate(
                [
                    'temporary_Patient_Id'         => $patientID,
                    'slot_Number'                  => $slotNo,
                ],
                [
                    'temporary_Patient_Id'         => $patientID,
                    'appointment_center_id'        => $centerID,
                    'appointment_section_id'       => $sectionID,
                    'appointment_ReferenceNumber'  => $refno,
                    'reason_for_Visit'             => $request->selectedDoctorRemarks,
                    'appointment_Date'             => $date,
                    'doctor_Id'                    => $doctorId,
                    'doctors_Request_Path'         => $doctorsFile,
                    'appointment_Time'             => '',
                    'status_Id'                    =>1,
                    'slot_Number'                  => $slotNo,
                    'total_Amount'                 => $request->selectedTotalAmount,
                ]
            );

            // Initialize variable to store the path of the uploaded file
            $proofs = '';
            // Check if a file was uploaded
            if ($request->hasFile('selectedPaymentProofFile')) {
                $file = $request->file('selectedPaymentProofFile');

                // Validate the file (optional, but recommended)
                $request->validate([
                    'selectedPaymentProofFile' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation
                ]);

                // Check if the file is valid
                if ($file->isValid()) {
                    // Generate a unique filename
                    $filename = time() . '_' . $file->getClientOriginalName();

                    // Store the file in the 'public/proofs' directory
                    $path = $file->storeAs('proofs', $filename, 'public');
                    $proofs = $path;
                } else {
                    return response()->json(['error' => 'Invalid file upload'], 400);
                }
            } else {
                return response()->json(['error' => 'No file was uploaded'], 400);
            }

            $patieintAppointment->payments()->updateOrCreate(
                [
                    // 'appointment_ReferenceNumber' => $sequence->seq_no,
                    'payment_Reference_Number' => $PaymentTransactionNo,
                ],
                [
                    'appointment_ReferenceNumber'   => $refno,
                    'payment_Reference_Number'      => $PaymentTransactionNo,
                    'payment_UploadPath'            => $proofs,
                    'status_Id'                     => 1,
                    'payment_Date'                  => Carbon::now()->format('Y-m-d'),
                    'payment_TotalAmount'           => $request->selectedTotalAmount,
                ]
            );

            foreach ($procedures as $row) {
                $patieintAppointment->transactions()->updateOrCreate(
                    [
                        'appointment_ReferenceNumber' => $refno,
                        'transaction_Code'            => $row['revenueid'],
                        'item_Id'                     => $row['item_id'],
                    ],
                    [
                        'appointment_ReferenceNumber' => $refno,
                        'transDate'                   => Carbon::now()->format('Y-m-d'),
                        'transaction_Code'            => $row['revenueid'],
                        'item_Id'                     => $row['item_id'],
                        'quantity'                    => 1,
                        'amount'                      => $row['price'],
                        'total_Amount'                => $row['price'] * 1,
                    ]
                );
            }
            SystemSequence::where(['isActive' => true, 'code' => 'APN'])->update(
                [
                    'seq_no' => $sequence->seq_no + 1,
                    'recent_generated' => $refno
                ]
            );


            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_patient_data')->commit();

            $data = [
                'patient_name'=> $patient_name,
                'date_schedule'=>$date,
                'slot'=>$slotNo,
                'amount'=>$totalAmount,
                'reference_no'=>$refno,
            ];
            $phoneNumberWithoutLeadingZero = ltrim($mobileno, '0');
            $helpersms = new SMSHelper();
            // $helpersms->sendSms($phoneNumberWithoutLeadingZero,SMSHelper::message($data));

            return response()->json(['data' => 'success'], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json($e->getMessage(), 200);
        }
    }

    public function slots(Request $request)
    {
        $selectedDate = Carbon::parse($request->date)->format('Y-m-d');
        $selectedSlot = $request->slot;
        $notAvailableSlots = PatientAppointment::select('slot_Number')->whereDate('appointment_Date', $selectedDate)->pluck('slot_Number')->toArray();
        $slots = [];
        for ($i = 1; $i < 50; $i++) {
            $slots[] = [
                'id' => $i,
                'available' => !in_array($i, $notAvailableSlots),
            ];
        }
        $selectedSlots = [];
        foreach ($notAvailableSlots as $value) {
            $selectedSlots[] = [
                'id' => (int)$value,
                'available' => false,
            ];
        }
        // Call the updateSlotsAvailability method and pass the slots and selectedSlots arrays
        $updatedSlots = $this->updateSlotsAvailability($slots, $selectedSlots);

        // Return the updated slots as a JSON response
        return response()->json($slots, 200);
    }

    public function getZipCode()
    {
        $query = Zipcode::with("getMunicipality", "getProvince");
        if (Request()->regionCode) {
            $query->where("regionCode", Request()->regionCode);
        }
        if (Request()->municipalityCode) {
            $query->where("municipalityCode", Request()->municipalityCode);
        }
        if (Request()->provinceCode) {
            $query->where("provinceCode", Request()->provinceCode);
        }
        $data = $query->get();
        return response()->json(['msg' => 'success', 'data' => $data]);
    }


    public function getBarangay(Request $request)
    {
        $regioncode = $request->region ?? '';
        $provincecode = $request->province ?? '';
        $municipality = $request->municipality ?? '';
        $data = Barangay::where('region_code', $regioncode)->where('province_code', $provincecode)->where('municipality_code', $municipality)->get();
        return response()->json($data, 200);
    }

    public function getnationality(Request $request)
    {
        $nationality = $request->nationality ?? '';
        $data['nationality'] = Nationalities::get();
        $data['civilstatus'] = CivilStatus::get();
        return response()->json($data, 200);
    }
}
