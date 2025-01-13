<?php
namespace App\Helpers\Appointment;

use App\Helpers\GetIP;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\UserAppointments;
use App\Models\HIS\PatientMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AppointmentEditHelper
{
    private static function calculateAge($birthdate)
    {
        return Carbon::parse($birthdate)->age;
    }
    //get the exist user information from appointmentPortaluser table 
    private function patientUserExistData($payload)
    {
        $id = $payload['id'];
        $data = UserAppointments::where('id',$id)->first();
        return $data;
    }

    private function userPatientData($payload,$patientUserExistData)
    {
        return [
        'lastname' => $payload['lastname'] ?? $patientUserExistData['lastname'] ?? null,
        'firstname' => $payload['firstname'] ?? $$patientUserExistData['firstname'] ?? null,
        'middle' => $payload['middle'] ?? $patientUserExistData['middle'] ?? null,
        'birthdate' => $payload['birthdate'] ?? $patientUserExistData['birthdate'] ?? null,
        'mobileno' => $payload['mobile_Number'] ?? $patientUserExistData['mobileno'] ?? null,
        'email' => $payload['email'] ?? $patientUserExistData['email'] ?? null,
        'password' =>  Hash::make($payload['password'] ?? $patientUserExistData['password'])  ?? null,
        'hostname' => (new GetIP())->getHostname() ?? null,
        'last_ipaddress' => (new GetIP())->value() ?? null,
        ];
    }
    //for Edit Temporary Appointment Data
    //get the exist patient Data by mathing user_id = id
    private function patientTemporayExistData($payload)
    {
        $id = $payload['id'];
        $data = PatientAppointmentsTemporary::where('user_id', $id)->first();
        return $data;
    }
    //store the data for update
    private function patientTemporayData($payload, $existTemporaryPatient)
    {
        return [
            'lastname' => $payload['lastname'] ?? $existTemporaryPatient['lastname'] ?? null,
            'firstname' => $payload['firstname'] ?? $existTemporaryPatient['firstname'] ?? null,
            'middlename' =>$payload['middlename'] ?? $existTemporaryPatient['middlename'] ?? null,
            'email_Address' => $payload['email_Address'] ?? $existTemporaryPatient['email_Address'] ?? null,
            'birthdate' => $payload['birthdate'] ?? $existTemporaryPatient['birthdate'] ?? null,
            'suffix' => $payload['suffix'] ?? $existTemporaryPatient['suffix'] ?? null,
            'sex_Id' => $payload['sex_Id'] ?? $existTemporaryPatient['sex_Id'] ?? null,
            'birthplace' => $payload['birthplace'] ?? $existTemporaryPatient['birthplace'] ?? null,
            'age' => self::calculateAge($payload['birthdate'] ?? $existTemporaryPatient['birthdate'] ) ?? null,
            'region_Id' => $payload['region_Id'] ?? $existTemporaryPatient['region_Id'] ?? null,
            'bldgstreet' => $payload['bldgstreet'] ?? $existTemporaryPatient['bldgstreet'] ?? null,
            'province_Id' => $payload['province_Id'] ?? $existTemporaryPatient['province_Id'] ?? null,
            'municipality_Id' => $payload['municipality_Id'] ?? $existTemporaryPatient['municipality_Id'] ?? null,
            'barangay_Id' => $payload['barangay_Id'] ?? $existTemporaryPatient['barangay_Id'] ?? null,
            'zipcode_Id' => $payload['zipcode_Id'] ?? $existTemporaryPatient['zipcode_Id'] ?? null,
            'mobile_Number' => $payload['mobile_Number'] ?? $existTemporaryPatient['mobile_Number'] ?? null,
            'civil_Status_Id' => $payload['civil_status'] ?? $existTemporaryPatient['civil_Status_Id'] ?? null,
            'nationality_Id' => $payload['nationality_Id'] ?? $existTemporaryPatient['nationality_Id'] ?? null,
        ];
    }
    
    private function patientMasterExistData($patient_Id)
    {   
        $data = PatientMaster::where('patient_Id', $patient_Id)->first();
        return $data;
    }

    private function patientMasterData($payload, $existPatientMasterData)
    {
        return
        [
            'title_id' => $payload['title_id'] ??  $existPatientMasterData['title_id'] ?? null,
            'lastname' => $payload['lastname'] ??  $existPatientMasterData['lastname'] ?? null,
            'firstname' => $payload['firstname'] ?? $existPatientMasterData['firstname'] ?? null,
            'middlename' => $payload['middlename'] ??  $existPatientMasterData['middlename'] ?? null,
            'suffix_id' => $payload['suffix_id'] ??  $existPatientMasterData['suffix_id'] ?? null,
            'typeofbirth_id' => $payload['typeofbirth_id'] ??  $existPatientMasterData['typeofbirth_id'] ?? null,
            'birthdate' => $payload['birthdate'] ??  $existPatientMasterData['birthdate'] ?? null,
            'birthtime' => $payload['birthtime'] ??  $existPatientMasterData['birthtime'] ?? null,
            'birthplace' =>$payload['birthplace'] ??  $existPatientMasterData['birthplace'] ?? null,
            'age' => self::calculateAge($payload['birthdate'] ?? $existPatientMasterData['birthdate'] ) ?? null,
            'sex_id' => $payload['sex_Id'] ??  $existPatientMasterData['sex_id'] ?? null,
            'nationality_id' => $payload['nationality_Id'] ??   $existPatientMasterData['nationality_id'] ?? null,
            'citizenship_id' => $payload['citizenship_id'] ??  $existPatientMasterData['citizenship_id'] ?? null,
            'complexion' => $payload['complexion'] ??  $existPatientMasterData['complexion'] ??null,
            'haircolor' => $payload['haircolor'] ??  $existPatientMasterData['haircolor'] ?? null,
            'eyecolor' => $payload['eyecolor'] ??  $existPatientMasterData['eyecolor'] ?? null,
            'height' => $payload['height'] ??  $ $existPatientMasterData['height'] ?? null,
            'weight' => $payload['weight'] ??  $existPatientMasterData['weight'] ?? null,
            'religion_id' => $payload['religion_Id'] ??  $existPatientMasterData['religion_id'] ?? null,
            'civilstatus_id' => $payload['civil_Status_Id'] ??  $existPatientMasterData['civilstatus_id'] ?? null,
            'bloodtype_id' => $payload['bloodtype_id'] ??  $existPatientMasterData['bloodtype_id'] ?? null,
            'dialect_spoken' => $payload['dialect_spoken'] ??  $existPatientMasterData['dialect_spoken'] ?? null,
            'bldgstreet' => $payload['bldgstreet'] ??  $existPatientMasterData['bldgstreet'] ?? null,
            'region_id' => $payload['region_Id'] ??  $existPatientMasterData['region_id'] ?? null,
            'province_id' => $payload['province_Id'] ??  $existPatientMasterData['province_id'] ??  null,
            'municipality_id' => $payload['municipality_Id'] ??  $existPatientMasterData['municipality_id'] ?? null,
            'barangay_id' => $payload['barangay_Id'] ??  $existPatientMasterData['barangay_id'] ?? null,
            'zipcode_id' => $payload['zipcode_Id'] ??  $existPatientMasterData['zipcode_id'] ?? null,
            'country_id' => $payload['country_id'] ??  $existPatientMasterData['country_id'] ?? null,
            'occupation' => $payload['occupation'] ??  $existPatientMasterData['occupation'] ?? null,
            'dependents' => $payload['dependents'] ?? $existPatientMasterData['dependents'] ?? null,
            'telephone_number' => $payload['telephone_Number'] ??  $existPatientMasterData['telephone_Number'] ?? null,
            'mobile_number' => $payload['mobile_Number'] ?? $existPatientMasterData['mobile_number'] ?? null,
            'email_address' => $payload['email_Address'] ??  $existPatientMasterData['email_address'] ?? null,
            'isSeniorCitizen' => $payload['isSeniorCitizen'] ??  $existPatientMasterData['isSeniorCitizen'] ?? null,
            'SeniorCitizen_ID_Number' => $payload['SeniorCitizen_ID_Number'] ??  $existPatientMasterData['SeniorCitizen_ID_Number'] ?? null,
            'isPWD' => $payload['isPWD'] ??  $existPatientMasterData['isPWD'] ?? null,
            'PWD_ID_Number' => $payload['PWD_ID_Number'] ??  $existPatientMasterData['PWD_ID_Number'] ?? null,
            'isPhilhealth_Member' => $payload['isPhilhealth_Member'] ??  $existPatientMasterData['isPhilhealth_Member'] ?? null,
            'Philhealth_Number' => $payload['Philhealth_Number'] ??  $existPatientMasterData['Philhealth_Number'] ?? null,
            'isEmployee' => $payload['isEmployee'] ??  $existPatientMasterData['isEmployee'] ?? null,
            'GSIS_Number' => $payload['GSIS_Number'] ??  $existPatientMasterData['GSIS_Number'] ?? null,
            'SSS_Number' => $payload['SSS_Number'] ??  $existPatientMasterData['SSS_Number'] ??  null,
            'passport_number' => $payload['passport_number'] ??   $existPatientMasterData['passport_number'] ?? null,
            'seaman_book_number' => $payload['seaman_book_number'] ??  $existPatientMasterData['seaman_book_number'] ?? null,
            'embarked_date' => $payload['embarked_date'] ??  $existPatientMasterData['embarked_date'] ?? null,
            'disembarked_date' => $payload['disembarked_date'] ??  $existPatientMasterData['disembarked_date'] ?? null,
            'xray_number' => $payload['xray_number'] ??  $existPatientMasterData['xray_number'] ?? null,
            'ultrasound_number' => $payload['ultrasound_number'] ??  $existPatientMasterData['ultrasound_number'] ?? null,
            'ct_number' => $payload['ct_number'] ??  $existPatientMasterData['ct_number'] ?? null,
            'petct_number' => $payload['petct_number'] ??   $existPatientMasterData['petct_number'] ?? null,
            'mri_number' => $payload['mri_number'] ??  $existPatientMasterData['mri_number'] ?? null,
            'mammo_number' => $payload['mammo_number'] ??  $existPatientMasterData['mammo_number'] ?? null,
            'OB_number' => $payload['OB_number'] ??  $existPatientMasterData['OB_number'] ?? null,
            'nuclearmed_number' => $payload['nuclearmed_number'] ??  $existPatientMasterData['nuclearmed_number'] ?? null,
            'typeofdeath_id' => $payload['typeofdeath_id'] ??   $existPatientMasterData['typeofdeath_id'] ?? null,
            'dateofdeath' => $payload['dateofdeath'] ??  $existPatientMasterData['dateofdeath'] ?? null,
            'timeofdeath' => $payload['timeofdeath'] ??   $existPatientMasterData['timeofdeath'] ?? null,
            'spDateMarried' => $payload['spDateMarried'] ??  $existPatientMasterData['spDateMarried'] ?? null,
            'spLastname' => $payload['spLastname'] ??  $existPatientMasterData['spLastname'] ?? null,
            'spFirstname' => $payload['spFirstname'] ??  $existPatientMasterData['spFirstname'] ??null,
            'spMiddlename' => $payload['spMiddlename'] ??  $existPatientMasterData['spMiddlename'] ?? null,
            'spSuffix_id' => $payload['spSuffix_id'] ??  $existPatientMasterData['spSuffix_id'] ?? null,
            'spAddress' => $payload['spAddress'] ??   $existPatientMasterData['spAddress'] ?? null,
            'sptelephone_number' => $payload['sptelephone_number'] ??  $existPatientMasterData['sptelephone_number'] ?? null,
            'spmobile_number' => $payload['spmobile_number'] ??  $existPatientMasterData['spmobile_number'] ?? null,
            'spOccupation' => $payload['spOccupation'] ?? $existPatientMasterData['spOccupation'] ?? null,
            'spBirthdate' => $payload['spBirthdate'] ??  $existPatientMasterData['spBirthdate'] ?? null,
            'spAge' => $payload['spAge'] ??  $existPatientMasterData['spAge'] ?? null,
            'motherLastname' => $payload['motherLastname'] ??  $existPatientMasterData['motherLastname'] ?? null,
            'motherFirstname' => $payload['motherFirstname'] ??  $existPatientMasterData['motherFirstname'] ?? null,
            'motherMiddlename' => $payload['motherMiddlename'] ??  $existPatientMasterData['motherMiddlename'] ?? null,
            'motherSuffix_id' => $payload['motherSuffix_id'] ??  $existPatientMasterData['motherSuffix_id'] ?? null,
            'motherAddress' => $payload['motherAddress'] ??  $existPatientMasterData['motherAddress'] ?? null,
            'mothertelephone_number' => $payload['mothertelephone_number'] ??  $existPatientMasterData['mothertelephone_number'] ?? null,
            'mothermobile_number' => $payload['mothermobile_number'] ??  $existPatientMasterData['mothermobile_number'] ?? null,
            'motherOccupation' => $payload['motherOccupation'] ??  $existPatientMasterData['motherOccupation'] ?? null,
            'motherBirthdate' => $payload['motherBirthdate'] ??  $existPatientMasterData['motherBirthdate'] ?? null,
            'motherAge' => $payload['motherAge'] ?? $existPatientMasterData['motherAge'] ?? null,
            'fatherLastname' => $payload['fatherLastname'] ??   $existPatientMasterData['fatherLastname'] ?? null,
            'fatherFirstname' => $payload['fatherFirstname'] ??  $existPatientMasterData['fatherFirstname'] ?? null,
            'fatherMiddlename' => $payload['fatherMiddlename'] ??   $existPatientMasterData['fatherSuffix_id'] ?? null,
            'fatherSuffix_id' => $payload['fatherSuffix_id'] ??   $existPatientMasterData['fatherSuffix_id'] ??  null,
            'fatherAddress' => $payload['fatherAddress'] ??  $existPatientMasterData['fatherAddress'] ??  null,
            'fathertelephone_number' => $payload['fathertelephone_number'] ??  $existPatientMasterData['fathertelephone_number'] ?? null,
            'fathermobile_number' => $payload['fathermobile_number'] ??   $existPatientMasterData['fathermobile_number'] ?? null,
            'fatherOccupation' => $payload['fatherOccupation'] ??  $existPatientMasterData['fatherOccupation'] ?? null,
            'fatherBirthdate' => $payload['fatherBirthdate'] ??  $existPatientMasterData['fatherBirthdate'] ?? null,
            'fatherAge' => $payload['fatherAge'] ??   $existPatientMasterData['fatherAge'] ?? null,
            'portal_access_uid' => $payload['portal_access_uid'] ??  $existPatientMasterData['portal_access_uid'] ?? null,
            'portal_access_pwd' => $payload['portal_access_pwd'] ??  $existPatientMasterData['portal_access_pwd'] ?? null,
            'isBlacklisted' => $payload['isBlacklisted'] ??   $existPatientMasterData['isBlacklisted'] ?? null,
            'blacklist_reason' => $payload['blacklist_reason'] ?? $ $existPatientMasterData['blacklist_reason'] ??  null,
            'isAbscond' => $payload['isAbscond'] ??  $existPatientMasterData['isAbscond'] ?? null,
            'abscond_details' => $payload['abscond_details'] ??  $existPatientMasterData['abscond_details'] ??  null,
            'is_old_patient' => $payload['is_old_patient'] ??  $existPatientMasterData['is_old_patient'] ?? null,
            'patient_picture' => $payload['patient_picture'] ??  $existPatientMasterData['patient_picture'] ?? null,
            'patient_picture_path' => $payload['patient_picture_path'] ??  $existPatientMasterData['patient_picture_path'] ?? null,
            'branch_id' => $payload['branch_id'] ??  $existPatientMasterData['branch_id'] ?? null,
            'previous_patient_id' => $payload['previous_patient_id'] ??  $existPatientMasterData['previous_patient_id'] ?? null,
            'medsys_patient_id' => $payload['medsys_patient_id'] ??  $existPatientMasterData['medsys_patient_id'] ?? null,
            'createdBy' => $payload['createdBy'] ??  $existPatientMasterData['createdBy'] ?? null,
            'created_at' => $payload['created_at'] ??  $existPatientMasterData['created_at'] ?? null,
            'updatedBy' => $payload['updatedBy'] ?? $existPatientMasterData['updatedBy'] ?? null,
        ];
    }


    public function processEditController($payload,$seq)
    {
        switch ($seq) {
            case '1':
                $patientUserExistData = $this->patientUserExistData($payload);
                $user = $this->userPatientData($payload,$patientUserExistData);
                UserAppointments::where('id',$payload['id'])->update($user);
                $existTemporaryPatient = $this->patientTemporayExistData($payload);
                $patient = $this->patientTemporayData($payload,$existTemporaryPatient);
                PatientAppointmentsTemporary::where('user_id', $payload['id'])->update($patient);
                $patient_Id = $existTemporaryPatient['patient_id'];
              if($patient_Id)
                {
                    $existPatientMasterData = $this->patientMasterExistData($patient_Id);
                    $master = $this->patientMasterData($payload,$existPatientMasterData); 
                    PatientMaster::where('patient_Id',$patient_Id)->update($master);
                }
              
                    $message = 'Edit Successfully';
                    return $message;
                break;
            
           
        }
    
    }

    // public function test($payload,$payloads)
    // {
    //     return 
    //     [
    //         'firstname' => $payload['firstname'],
    //         'lastname' => $payload['lastname'],
    //         'age'  => $payload['age'],
    //         'referenceNo' => $payloads
    //     ];
    // }
}