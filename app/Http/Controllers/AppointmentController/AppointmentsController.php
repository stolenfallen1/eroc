<?php

namespace App\Http\Controllers\AppointmentController;

use App\Helpers\Appointment\AppointmentHelper;
use App\Helpers\Appointment\AppointmentSequence;
use App\Http\Controllers\Controller;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\PatientAppointmentPayment;
use Illuminate\Http\Request;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\PatientAppointmentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use App\Helpers\SMSHelper;
use App\Models\Appointments\UserAppointments;
use Illuminate\Support\Facades\DB;


class AppointmentsController extends Controller
{

    protected $AppointmentHelper;
    protected $SequenceHelper;
    protected $SmsHelper;



    public function __construct()
    {
        $this->AppointmentHelper = new AppointmentHelper();
        $this->SequenceHelper = new AppointmentSequence();
        $this->SmsHelper = new SMSHelper();
    }
    public function getUserDetails(Request $request)
    {
        $payload = $request->all();
        $data = PatientAppointmentsTemporary::select('firstname', 'lastname')->where('user_id', $payload['id'])->first();
        return response()->json(['data' => $data], 200);
    }

    public function store(Request $request)
    {   
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $payload = $request->all();
            $payload['age'] = $this->AppointmentHelper::calculateAge($request->input('birthdate'));

            $existUser = UserAppointments::where('lastname', $payload['lastname'])
                ->where('firstname', $payload['firstname'])
                ->whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->first();

            $existTemporaryPatient = PatientAppointmentsTemporary::where('lastname', $payload['lastname'])
                ->where('firstname', $payload['firstname'])
                ->whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->first();
                
            $mobileNo = $payload['mobile_Number'] ?? $existUser['mobileno'];
            $mobile = ltrim($mobileNo, '0');
            $userdata = $this->AppointmentHelper->createOrUpdateUserAppointment($payload, $existUser, $mobile);

            
            $user = UserAppointments::whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->updateOrCreate(
                [
                    'lastname' => $payload['lastname'],
                    'firstname' => $payload['firstname'],
                ],
                $userdata
            );


          
            $id = $user->id;
           
            $temporarydata = $this->AppointmentHelper->createOrUpdatePatientAppointmentTemporary($payload, $existTemporaryPatient, $id,$mobile);
              PatientAppointmentsTemporary::whereDate('birthdate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->updateOrCreate(
                [
                    'lastname' => $payload['lastname'],
                    'firstname' => $payload['firstname'],
                ],
                $temporarydata
            );
           
        
            $token = $user->createToken();
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json(['user' => $user , 'message' => 'Register Success', 'api_token' => $user->api_token],201);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json(['message' => 'Failed to Book new appointments.', 'error' => $e->getMessage()], 500);
        }
    }

    public function store_appointment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        $startDate = Carbon::now()->startOfDay();
        $payload = $request->all();
        // return response()->json(['data'=>$payload],200);
        $today = Carbon::today()->format('Y-m-d');

        if ($today <= $payload['appointment_Date']) {

            $referenceNo = $this->SequenceHelper->getAppointmentReference();
            $imagePath = $this->AppointmentHelper->saveDoctorRequestImage($request, 'doctorRequest', $referenceNo);
            $slotNo = $this->SequenceHelper->getSlots($payload);
            $slotLimit = 20;

            $data = PatientAppointmentsTemporary::select('lastname', 'firstname', 'patient_Id', 'id', 'user_id')
                ->where('user_id', $payload['id'])->first();

            $patient_id = $data['patient_id'] ?? null;
            try {
                if ($slotNo <= $slotLimit) {
                    $appointmentData = $this->AppointmentHelper->createOrUpdatePatientAppointment($payload, $referenceNo, $slotNo, $imagePath, $patient_id);
                    PatientAppointment::whereDate('appointment_Date', Carbon::parse($payload['appointment_Date'])->format('Y-m-d'))->updateOrCreate(
                        [
                            'appointment_ReferenceNumber' => $referenceNo,
                        ],
                        $appointmentData
                    );
                    $transactions = $payload['appointment_transactions'];
                    $transactionItems = [];

                    foreach ($transactions['item_id'] as $key => $itemId) {
                        $transactionData = $this->AppointmentHelper->createOrUpdatePatientTransaction([
                            'item_id' => $itemId,
                            'price' => $transactions['prices']['price'][$key],
                            'transaction_Code' => $transactions['transaction_Code'][$key],

                        ], $referenceNo);

                        PatientAppointmentTransaction::updateOrCreate(
                            [
                                'item_id' => $transactionData['item_id'],
                                'appointment_referenceNumber' => $transactionData['appointment_referenceNumber'],
                                'transaction_Code' => $transactionData['transaction_Code'],
                            ],
                            $transactionData
                        );

                        $transactionItems[] = $transactionData;
                    }


                    DB::connection('sqlsrv_patient_data')->commit();
                    DB::connection('sqlsrv')->commit();

                    return response()->json(['message' => 'You successfully create new Appointment', 'slotNo' => $slotNo], 201);
                } else {
                    return response()->json(['message' => 'No Slot Date Available Today'], 500);
                }
            } catch (\Exception $e) {
                if ($imagePath && File::exists(public_path($imagePath))) {
                    File::delete(public_path($imagePath));
                }
                DB::connection('sqlsrv_patient_data')->rollBack();
                DB::connection('sqlsrv')->rollBack();
                return response()->json(['error' => $e->getMessage(), 'Failed to create new Appointment'], 500);
            }
        } else {
            return response()->json(['message' => 'Invalid Date'], 500);
        }
    }


    public function store_payment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $payload = $request->all();

            $imagePath = $this->AppointmentHelper->savePaymentRequestImage($request, 'paymentImage');
            // $query = PatientAppointmentsTemporary::select('mobile_Number')->where('id', $payload['id'])->first();
            // $mobile = $query['mobile_Number'];
            $paymentData = $this->AppointmentHelper->createOrUpdatePatientPayment($payload, $imagePath);


            PatientAppointmentPayment::where('payment_Date', Carbon::today())->updateOrCreate(
                [
                    'payment_Reference_Number' => $payload['payment_Reference_Number']
                ],
                $paymentData
            );
            //  $seq = 1;
            //  $message = $this->SmsHelper->data($payload,$seq);
            //  $this->SmsHelper->sendSms($mobile,$message);
            PatientAppointment::where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->update([
                'status_Id' => 2,
                'isSmsSend' => 1,
            ]);


            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json(['message' => 'Successfully Paid appointment'], 201);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json(['message' => 'Failed to Pay appointment', 'error' => $e->getMessage()], 500);
        }
    }


    public function getCurrentUserToken(Request $request)
    {
        try {
            $userId = $request->user_id;
            $apiToken = $request->api_token;


            return response()->json([

                'api_token' => $apiToken,
            ], 201);
        } catch (\Exception $e) {



            // Return a structured error response
            return response()->json([
                'message' => 'No Api Key',
                'error' => $e->getMessage()
            ], 500); // Internal Server Error status code
        }
    }
}
