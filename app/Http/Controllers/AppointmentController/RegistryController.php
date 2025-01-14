<?php

namespace App\Http\Controllers\AppointmentController;

use App\Helpers\Appointment\AppointmentSequence;
use App\Helpers\Appointment\RegistryHelper;
use App\Helpers\SMSHelper;
use App\Http\Controllers\Controller;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\PatientAppointmentCheckIn;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\services\PatientRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistryController extends Controller
{
    protected $sequenceHelper;
    protected $dataHelper;
    protected $SmsHelper;
    public function __construct()
    {
        $this->sequenceHelper = new AppointmentSequence();
        $this->dataHelper = new RegistryHelper();
        $this->SmsHelper = new SMSHelper();
    }
    
    public function AppointmentRegistry(Request $request)
    {

        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        try {

            $payload = $request->all();
            $patientData = PatientAppointmentsTemporary::where('id', $payload['temporary_Patient_Id'])->first();
            if ($patientData) {
                $patient_query = PatientMaster::select('patient_Id')
                    ->where('lastname', $patientData->lastname)
                    ->where('firstname', $patientData->firstname)
                    ->where('birthdate', $patientData->birthdate)
                    ->first();
                if ($patient_query) {
                    $patient_Id = $patient_query->patient_Id;
                    $case_query = PatientRegistry::select('case_No')->where('patient_Id', $patient_Id)
                        ->whereDate('registry_Date', Carbon::today()->toDateString())->first();

                    if ($case_query) {
                        $case_No = $case_query->case_No;
                    } else {
                        $query = $this->sequenceHelper->getCaseNoOnly();
                        $case_No = $query['case_No'];
                    }
                } else {
                    $query = $this->sequenceHelper->getBothSequence();
                    $patient_Id = $query['patient_Id'];
                    $case_No = $query['case_No'];
                }
            } else {
                return response()->json(['message' => 'No Patient Data Found Please Check Carefully and Try Again'], 500);
            }


            $masterData = $this->dataHelper->updateOrCreatePatientMaster($patientData, $patient_Id, $payload);

            PatientMaster::whereDate('birthdate', Carbon::parse($patientData['birthdate'])->format('Y-m-d'))->updateOrcreate(
                [
                    'lastname' => $patientData['lastname'],
                    'firstname' => $patientData['firstname'],
                ],

                $masterData
            );

            $registryData = $this->dataHelper->UpdateOrCreateCaseNo($payload, $patient_Id, $case_No);

            PatientRegistry::whereDate('registry_Date', Carbon::today()->toDateString())->updateOrcreate(
                [
                    'case_No' => $case_No
                ],
                $registryData
            );

            PatientAppointment::where('temporary_Patient_Id', $patientData->id)
                ->where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->update([
                    'patient_id' => $patient_Id,
                    'case_no'    => $case_No,
                    'status_Id' => 1,
                    'confirmation_Date' => Carbon::today(),
                ]);

            PatientAppointmentsTemporary::where('id', $patientData->id)->orWhere('id', $payload['temporary_Patient_Id'])
                ->firstOrFail()
                ->update([
                    'patient_id' => $patient_Id,
                ]);

            // $seq = 2;
            // $message = $this->SmsHelper->data($payload,$seq);

            DB::connection('sqlsrv_medsys_patient_data')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv')->commit();
            return response()->json(['message' => 'Successfully confirmed Appointment'], 201);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv')->rollBack();
            return response()->json(['message' => 'Failed to Confrim Patient.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getSlot(Request $request)
    {
        $payload = $request->all();
        $startDate = Carbon::now()->startOfDay();
        $selectedDate = $payload['appointment_Date'] ?? $startDate->format('Y-m-d');
        $slotNo = PatientAppointment::where('appointment_Date', '>=', $selectedDate)->whereIn('status_Id', [0,1, 2, 3])->count();
        return $slotNo + 1;
    }
 

    public function seclectedSlot(Request $request)
    {
        $payload = $request->all();
    
        $selectedDate = $payload['appointment_Date'];
    
        // Count appointments matching the criteria
        $appointmentCount = PatientAppointment::where('appointment_Date', '>=', $selectedDate)
            ->whereIn('status_Id', [0,1, 2, 3])
            ->count();
            $slotNo = $appointmentCount + 1;
        if ( $slotNo <= 2) {
            
            return response()->json([
                'data' => $slotNo,
                'message' => "Your slot has been reserved successfully! Your slot number is $slotNo. Thank you!"
            ], 201);
        } else {
            return response()->json([
                'data' => 0,
                'message' => "Unfortunately, no slot number is available today. Please select another date. Thank you!",
                'status' => 'full'
            ], 200);
        }
    }

    public function AppointmentCheckIn(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        $startDate = Carbon::now()->startOfDay();
        $series = PatientAppointmentCheckIn::where('checkin_Time', '>=', $startDate->format('Y-m-d'))->count();

        if ($series < 20) {
            try {

                $payload = $request->all();

                $appointment = PatientAppointment::select('patient_id', 'case_no', 'appointment_ReferenceNumber')
                    ->where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->first();
                $checkIn_Data = $this->dataHelper->UpdateOrCreateCheckIn($payload, $appointment);

                PatientAppointmentCheckIn::updateOrcreate(
                    [
                        'appointment_ReferenceNumber' => $payload['appointment_ReferenceNumber']
                    ],
                    $checkIn_Data
                );
                // $seq = 3;
                // $message = $this->SmsHelper->data($payload,$seq);


                DB::connection('sqlsrv_patient_data')->commit();
                return response()->json(['message' => 'Successfully Appointment Check In'], 201);
            } catch (\Exception $e) {

                DB::connection('sqlsrv_patient_data')->rollBack();
                return response()->json(['message' => 'Failed to Confrim Patient.', 'error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'CheckIn today is Already at Limit'], 500);
        }
    }
    public function DoneAppointment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        $payload = $request->all();
        
        try {

            PatientAppointment::where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])
                ->firstOrFail()->update(
                    [
                        'status_Id' => 0
                    ]
                );
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json(['message' => 'Successfully Mark As Done Appointment Reference No.' . $payload['appointment_ReferenceNumber']], 201);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json(['Error' => $e->getMessage(), 'message' => 'Unsuccessfully Mark As Done Appointment Reference No.' . $payload['appointment_ReferenceNumber']], 500);
        }
    }
    public function Reminder(Request $request)
    {
        try {
            $payload = $request->all();
            $seq = 4;
            $this->SmsHelper->data($payload, $seq);
            return response()->json(['message' => 'Successfully Sent SMS message'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Unsuccessfully sent SMS message'], 500);
        }
    }

 
    public function RescheduleAppointment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $payload = $request->all();
            $today = Carbon::today()->format('Y-m-d');
            $slotNo = $this->sequenceHelper->getSlots($payload);

            if ($today <= $payload['appointment_Date'] && $slotNo <= 20) {
                PatientAppointment::where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->update(
                    [
                        'appointment_Date' => $payload['appointment_Date'],
                        'slot_Number'  => $slotNo,
                    ]
                );
                // // $old =  PatientAppointment::select('patient_Id', 'case_No')->where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->first();
                // $case_No = $old['case_No'];
                $case_No = $payload['case_No'];
                if ($case_No) {
                    PatientRegistry::where('case_No', $case_No)->where('patient_Id', $payload['patient_Id'])->update(
                        [
                            'registry_Date' => $today
                        ]
                    );
                }
                
                // DB::connection('sqlsrv_patient_data');
                return response()->json(['message' => 'Successfully Reschudle Appointment'], 201);
            } else {
                return response()->json([
                    'message' => 'The selected date has reached its limit or the provided date is invalid.'
                ], 500);
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollback();
            return response()->json(['error' => $e->getMessage(), 'message' => 'Unsuccessful Reschedule Please Try Again'], 500);
        }
    }

    public function CancelAppointment(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        $payload = $request->all();
        try {
            PatientAppointment::where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])
                ->update([
                    'status_Id' => 5,
                    'cancellation_Date' => Carbon::today(),
                ]);
            //  DB::connection('sqlsrv_patient_data')->commit();
            return response()->json(['message', 'Succesfully Appointment Cancel'], 201);
        } catch (\Exception $e) {
            DB::connection()->rollBack('sqksrv_patient_data');
            return response()->json(['error' => $e->getMessage(), 'message' => 'Unsuccessful Cancel Appointment'], 500);
        }
    }
}
