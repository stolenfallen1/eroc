<?php

namespace App\Helpers;

use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\PatientAppointment;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\BuildFile\SystemSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SMSHelper
{

    public function sendSms($mobile,$message){
        // Set the recipients and message
        $contact = '63'.$mobile;
       
        $url = 'https://api.m360.com.ph/v3/api/broadcast';
        $API_KEY = 'fxkeZFC21wJmBAKL';
        $API_secret = '3iMuiQu2jIOTYkIZ8kWJbmhoU83pbl4R';
        $payload = [
            'app_key' => $API_KEY,
            'app_secret' => $API_secret,
            'msisdn' => $contact,
            'shortcode_mask' => 'CebuDoc',
            'content' => $message,
            'rcvd_transid' => '',
            'is_intl' => false,
            'dcs'=>0
        ];

        try {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_message = curl_error($ch);
                curl_close($ch);
                return "cURL Error: " . $error_message;
            }

            curl_close($ch);

            return $response;
        } catch (\Exception $e) {
            // Handle exceptions (e.g., logging, throwing custom exceptions)
            return $e->getMessage();
        }
    }

    public static function message($data, $patientName)
    {

        $name           = ucwords($patientName['lastname']) . ' ' . ucwords($patientName['firstname']);
        $dateSchedule   = $data['appointment_Date'];
        $status         = 'Pending';
        $amount         = number_format($data['total_Amount'], 2);
        $referenceno    = $data['appointment_ReferenceNumber'];
        $slot           = $data['slot_Number'];

        $message = " " . $name . "!\n\n";
        $message .= "Thank you for booking. Your booking with ref no: $referenceno has been received and will be processed promptly. Kindly wait for the text confirmation that will be sent to you shortly.\n\n";
        $message .= "Booking details: \n\n";
        $message .= "Schedule Date : $dateSchedule\n";
        $message .= "Slot No : $slot\n";
        $message .= "Scheduled Status : $status\n";
        $message .= "Amount Paid : $amount\n";
        $message .= '';
        return $message;
    }

    public static function confirmed_message($data, $patientName)
    {

        $name           = ucwords($patientName['lastname']) . ' ' . ucwords($patientName['firstname']);
        $dateSchedule   = $data['appointment_Date'];
        $referenceno    = $data['appointment_ReferenceNumber'];



        $message =  $name . "!\n\n";
        $message .= "Your booking with reference number $referenceno has been confirmed.";
        $message .= "\n\n";
        $message .= "We' be happy to serve you on your scheduled date  $dateSchedule\n\n";
        $message .= '';
        return $message;
    }

    public static function checkedIn_message($data, $patientName)
    {

        $name           = ucwords($patientName['lastname']) . ' ' . ucwords($patientName['firstname']);
        $dateSchedule   = $data['appointment_Date'];
        $referenceno    = $data['appointment_ReferenceNumber'];
        $slot           = $data['slot_Number'];

        $message =  $name . "!\n\n";
        $message .= "Your booking with reference number $referenceno has been successfully checked in. Please present this reference to the receptionist along with your slot number.";
        $message .= "\n";
        $message .= "Your Slot No. $slot";
        $message .= "\n\n";
        $message .= "We be happy to serve you on your scheduled date  $dateSchedule\n\n";
        $message .= '';
        return $message;
    }


    public static function sendTextMessage($patientName, $center)
    {
        $name           = ucwords($patientName['lastname']) . ' ' . ucwords($patientName['firstname']);
        $center = ucwords($center['title']);

        $message =  "Dear " . $name . "!\n\n";
        $message .= "This is a reminder of your appointment at $center on . Please arrive at least 30 minutes before your scheduled time to complete necessary preparations. Thank you!";
        $message .= '';
        return $message;
    }

    public static function sendReschedule( $payload ,$slotNo) 
    {
        $data = PatientAppointment::select('total_Amount', 'appointment_ReferenceNumber', 'temporary_Patient_Id', 'appointment_Date', 'slot_Number', 'appointment_center_id')->where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->first();
        $patientName = PatientAppointmentsTemporary::select('lastname', 'firstname')->where('id', $data['temporary_Patient_Id'])->first();

        $name         = ucwords($patientName['lastname']) . ' ' . ucwords($patientName['firstname']);
        $newSchedule  = $payload['appointment_Date'];
        $referenceno  = $data['appointment_ReferenceNumber'];
        $newSlot      = $slotNo ;
    
        $message  = "Dear $name,\n\n";
        $message .= "We would like to inform you that your booking with reference number $referenceno has been successfully rescheduled. Please present this reference to the receptionist along with your slot number.\n\n";
        $message .= "New Slot Number: $newSlot\n";
        $message .= "Scheduled Date: $newSchedule\n\n";
        $message .= "We look forward to serving you on your updated appointment date. If you have any questions, feel free to contact us.\n\n";
        $message .= "Thank you for choosing our services.";
    
        return $message;
    }
    
    public function data($payload, $seq )
    {
        $data = PatientAppointment::select('total_Amount', 'appointment_ReferenceNumber', 'temporary_Patient_Id', 'appointment_Date', 'slot_Number', 'appointment_center_id')->where('appointment_ReferenceNumber', $payload['appointment_ReferenceNumber'])->first();
        $patientName = PatientAppointmentsTemporary::select('lastname', 'firstname')->where('id', $data['temporary_Patient_Id'])->first();
       
        switch ($seq) {
            case '1':
                $message = $this->message($data, $patientName);
                return $message;
                break;
            case '2':
                $message = $this->confirmed_message($data, $patientName);
                return $message;
                break;
            case '3':
                $message = $this->checkedIn_message($data, $patientName);
                return $message;
                break;
            case '4':
                $center  = AppointmentCenter::select('title')->where('id', $data['appointment_center_id'])->where('isactive', 1)->first();
                $message = $this->sendTextMessage($patientName, $center);
                return $message;
                break;
           
            default:
                $message = "No message";
                break;
        }
    }

              
}
