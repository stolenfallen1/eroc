<?php

namespace App\Helpers\Appointment;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Helpers\GetIP;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\UserAppointments;
use App\Models\Appointments\PatientAppointmentsTemporary;
use Carbon\Carbon;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Cache;

class AppointmentHelper
{

    public static function calculateAge($birthdate)
    {
        return Carbon::parse($birthdate)->age;
    }

    public function createOrUpdateUserAppointment($payload, $existUser,$mobile)
    {

        return
            [

                'lastname' => ucwords($payload['lastname'] ?? $existUser['lastname'])  ?? null,
                'firstname' => ucwords($payload['firstname'] ?? $existUser['firstname']) ?? null,
                'middle' => $payload['middle']  ?? null,
                'birthdate' => $payload['birthdate'] ?? $existUser['birthdate'] ?? null,
                'mobileno' => $mobile ?? null,

                'email' => $payload['email'] ?? $existUser['email'] ?? null,
                'password' => isset($payload['password'])
                    ? Hash::make($payload['password']) // Hash a new password if provided
                    : ($existUser['password'] ?? null),
                'created_at' => Carbon::today(),
                'isactive' => 1,
                'dateLogin' => Carbon::today() ?? null,
                'datelogout' => '' ?? null,
                'hostname' => (new GetIP())->getHostname() ?? null,
                'last_ipaddress' => (new GetIP())->value() ?? null,

            ];
    }

    public function createOrUpdatePatientAppointmentTemporary($payload, $existTemporaryPatient, $id,$mobile)
    {
        return      [
            'Type_Id' => '',
            'patient_id' => '',
            'lastname' => ucwords($payload['lastname'] ?? $existTemporaryPatient['lastname'])  ?? null,
            'firstname' => ucwords($payload['firstname'] ?? $existTemporaryPatient['firstname'] ) ?? null,
            'middlename' => $payload['middle']  ?? null,
            'email_Address' => $payload['email'] ?? $existTemporaryPatient['email_Address'] ?? null,
            'birthdate' => $payload['birthdate']  ?? $existTemporaryPatient['birthdate']  ?? null,
            'branch_Id' => $payload['branch_Id'] ?? 1,
            'suffix' => $payload['suffix'] ?? $existTemporaryPatient['suffix'] ?? null,
            'sex_Id' => $payload['sex_Id'] ?? $existTemporaryPatient['sex_Id'] ?? null,
            'birthplace' => $payload['birthplace'] ?? $existTemporaryPatient['birthplace'] ?? null,
            'age' => self::calculateAge($payload['birthdate']) ?? $existTemporaryPatient['age'] ?? null,
            'region_Id' => $payload['region_Id'] ?? $existTemporaryPatient['region_Id'] ?? null,
            'bldgstreet' => $payload['bldgstreet'] ?? $existTemporaryPatient['bldgstreet'] ?? null,
            'province_Id' => $payload['province_Id'] ?? $existTemporaryPatient['province_Id'] ?? null,
            'municipality_Id' => $payload['municipality_Id'] ?? $existTemporaryPatient['municipality_Id'] ?? null,
            'barangay_Id' => $payload['barangay_Id'] ?? $existTemporaryPatient['barangay_Id'] ?? null,
            'zipcode_Id' => $payload['zipcode_Id'] ?? $existTemporaryPatient['zipcode_Id'] ?? null,
            'mobile_Number' => $mobile ?? null,
            'civil_Status_Id' => $payload['civil_status'] ?? $existTemporaryPatient['civil_Status_Id'] ?? null,
            'nationality_Id' => $payload['nationality_Id'] ?? $existTemporaryPatient['nationality_Id'] ?? null,
            'user_id' => $id,
            'created_at' => Carbon::today(),
        ];
    }

    public function saveDoctorRequestImage(Request $request, $fileInputName, $referenceNo)
    {
        // Check if the request contains a file for the given input name
        if ($request->hasFile($fileInputName)) {
            // Get the uploaded file
            $file = $request->file($fileInputName);

            // Generate a unique file name using the reference number and timestamp
            $filename = $referenceNo . '-' . time() . '.' . $file->getClientOriginalExtension();

            // Define the path where the image will be saved
            $filePath = 'appointmentImage/Transaction/' . $filename;

            // Save the file to the public directory (public/appointmentImage/Transaction)
            $file->move(public_path('appointmentImage/Transaction'), $filename);

            // Return the file path, which will be saved to the database
            return $filePath;
        }

        // Return null if no file was uploaded
        return null;
    }


    public function savePaymentRequestImage(Request $request, $fileInputName)
    {
        // Check if the request contains a file for the given input name
        if ($request->hasFile($fileInputName)) {
            // Get the uploaded file
            $file = $request->file($fileInputName);

            // Generate a unique file name using the reference number and timestamp
            $filename =  time() . '.' . $file->getClientOriginalExtension();

            // Define the path where the image will be saved
            $filePath = 'appointmentImage/Payment/' . $filename;

            // Save the file to the public directory (public/appointmentImage/Transaction)
            $file->move(public_path('appointmentImage/Payment'), $filename);

            // Return the file path, which will be saved to the database
            return $filePath;
        }

        // Return null if no file was uploaded
        return null;
    }

    public function createOrUpdatePatientAppointment($payload, $referenceNo,  $slotNo, $imagePath, $patient_id)
    {

        $data = PatientAppointmentsTemporary::select('lastname', 'firstname', 'patient_Id', 'id', 'user_id')
            ->where('user_id', $payload['id'])
            ->first();

        $fullname = $data['firstname'] . ' ' . $data['lastname'];
        return [
            'patient_id' => $patient_id ?? null,
            'temporary_Patient_Id' => $data['id'] ?? null,
            'case_No' => 0 ?? null,
            'appointment_Type_Id' => $payload['appointment_Type_Id'] ?? 1,
            'appointment_ReferenceNumber' =>  $referenceNo,
            'doctor_Id' =>  $payload['doctor_Id'] ?? null,
            'doctors_Request_Path' =>  $imagePath ?? null, // This will be the image path
            'appointment_Date' =>  $payload['appointment_Date'] ?? null,
            'appointment_Time' =>  $payload['appointment_Time'] ?? null,
            'slot_Number' =>  $slotNo,
            'total_Amount' =>  $payload['total_Amount'] ?? null,
            'isOnline' => 0,
            'isEmailed' => 0,
            'isSmsSend' => 0,
            'reason_for_Visit' => $payload['reason_for_Visit'] ?? null,
            'confirmation_Date' =>  $payload['confirmation_Date'] ?? null,
            'cancellation_Date' =>  $payload['cancellation_Date'] ?? null,
            'status_id' =>  $payload['status_Id'] ?? 3,
            'createdBy' =>  $fullname ?? null,
            'created_at' => Carbon::now(),
            'updatedby' => '',
            'updated_at' => '',
            'appointment_center_id' => $payload['appointment_center_id'] ?? null,
            'appointment_section_id' =>  $payload['appointment_section_id'] ?? null,
        ];
    }
    public function createOrUpdatePatientTransaction($payload, $referenceNo)
    {
        return [
            'appointment_referenceNumber' => $referenceNo,
            'transDate' => Carbon::today()->toDateString(),
            'item_id' => $payload['item_id'],
            'quantity' => 1,
            'amount' => $payload['price'],
            'transaction_Code' => $payload['transaction_Code'],
            'discount_TypeId' => $payload['discount_TypeId'] ?? null,
            'discount_Name' => $payload['discount_Name'] ?? null,
            'discount_Amount' => $payload['discount_Amount'] ?? null,
            'isVatable' => $payload['isVatable'] ?? null,
            'vat_Amount' => $payload['vat_Amount'] ?? null,
            'net_Amount' => $payload['net_Amount'] ?? null,
            'total_Amount' => $payload['total_Amount'] ?? null,
            'createdby' => $payload['id'] ?? null,
            'updatedby' => $payload['user_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }



    public function createOrUpdatePatientPayment($payload, $imagePath)
    {
        return
            [
                'appointment_ReferenceNumber' => $payload['appointment_ReferenceNumber'] ?? null,
                'payment_Method_Id' => $payload['payment_Method_Id'] ?? null,
                'payment_Reference_Number' => $payload['payment_Reference_Number'] ?? null,
                'payment_UploadPath' => $imagePath ?? null,
                'payment_Date'  => Carbon::today(),
                'payment_TotalAmount' => $payload['payment_totalAmount'] ?? 0,
                'status_Id'  => $payload['status_Id'] ?? 1,
                'createdby'   => $payload['createdby'] ?? null,
                'created_at' => Carbon::today() ?? null,
                'updatedby' => $payload['updatedby'] ?? null,
                'updated_at' => $payload['updated_at'] ?? null,
            ];
    }

    public function LoginTimeIn()
    {
        return
            [
                'dateLogin' => Carbon::now() ?? null,
                'hostname' => (new GetIP())->getHostname() ?? null,
                'last_ipaddress' => (new GetIP())->value() ?? null,

            ];
    }

    public function loginTimeOut()
    {
        return
            [
                'datelogout' => Carbon::today() ?? null,
                'hostname' => (new GetIP())->getHostname() ?? null,
                'last_ipaddress' => (new GetIP())->value() ?? null,

            ];
    }
}
