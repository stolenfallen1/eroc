<?php

namespace App\Helpers;

use App\Models\BuildFile\SystemSequence;
use Illuminate\Support\Facades\Auth;

class SMSHelper {

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

    public static function message($data){

        $name           = ucwords($data['patient_name']);
        $dateSchedule   = $data['date_schedule'];
        $status         = 'Pending';
        $amount         = number_format($data['amount'],2);
        $referenceno    = $data['reference_no'];
        $slot           = $data['slot'];

        $message =" ".$name."!\n\n";
        $message .= "Thank you for booking. Your booking with ref no: $referenceno has been received and will be processed promptly. Kindly wait for the text confirmation that will be sent to you shortly.\n\n";
        $message .= "Booking details: \n\n";
        $message .= "Schedule Date : $dateSchedule\n";
        $message .= "Slot No : $slot\n";
        $message .= "Scheduled Status : $status\n";
        $message .= "Amount Paid : $amount\n";
        $message .= '';
        return $message;
    }

}