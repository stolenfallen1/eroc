<?php 

    namespace App\Helpers\HIS;

    class PatientMasterObserverHelper {
        public $account_Trans_Types;
        public function medsysPatientMasterData($patient) {
            return [
                'HospNum'       => $patient->patient_Id   ?? '',
                'LastName'      => $patient->lastname     ?? '',
                'FirstName'     => $patient->firstname    ?? '',
                'MiddleName'    => $patient->middlename   ?? '',
                'AccountNum'    => '',
                'HouseStreet'   => $patient->bldgstreet   ?? '',
                'Barangay'      => $patient->barangay_id  ?? '',
                'Sex'           => $patient->sex_id       ?? '',
                'BirthDate'     => $patient->birthdate    ?? '',
                'CivilStatus'   => $patient->civil_Status ?? '',
                'Age'           => $patient->patient_Age  ?? ''
            ];
        }

        public function medsysPatientMaster2Data($patient) {
            return [
                'HospNum'               => $patient->patient_Id,
                    'BirthPlace'        => $patient->birthplace,
                    'NationalityID'     => $patient->nationality_id,
                    'ReligionID'        => $patient->religion_id,
                    'Spouse'            => ($patient->spLastname || $patient->spFirstname) 
                                        ? $patient->spLastname . ', ' . $patient->spFirstname . ' ' . $patient->spMiddlename
                                        : '',
                    'SpouseAddress'     => $patient->spAddress ?? ' ',
                    'SpouseTelNum'      => $patient->sptelephone_number,
                    'Father'            => ($patient->fatherLastname || $patient->fatherFirstname)
                                        ? $patient->fatherLastname . ', ' . $patient->fatherFirstname . ' ' . $patient->fatherMiddlename
                                        : '',
                    'FatherAddress'     => $patient->fatherAddress ?? ' ',
                    'FatherTelNum'      => $patient->fathertelephone_number,
                    'Mother'            => ($patient->motherLastname || $patient->motherFirstname)
                                        ? $patient->motherLastname . ', ' . $patient->motherFirstname . ' ' . $patient->motherMiddlename
                                        : '',
                    'MotherAddress'     => $patient->motherAddress,
                    'MotherTelNum'      => $patient->mothertelephone_number,
            ];
        }
    }