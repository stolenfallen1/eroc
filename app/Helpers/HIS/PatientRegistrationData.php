<?php 

    namespace App\Helpers\HIS;

    use \Carbon\Carbon;
    use App\Helpers\GetIP;
    use App\Models\HIS\services\Patient;
    use App\Models\HIS\services\PatientRegistry;

    class PatientRegistrationData {

        public function handleExistingPatientData($lastname, $firstname) {
            $existingPatient = Patient::where('lastname', $lastname)
            ->where('firstname', $firstname)
            ->first();
            return $existingPatient;
        }

        public function handleExistingRegistryData($patient_id, $today) {
            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
            ->whereDate('registry_Date', $today)
            ->exists();
            return $existingRegistry;
        }
        public function preparePatientData($request, $checkUser, $currentTimestamp, $patientId, $existingData) {
            return [
                'patient_Id'                => $request->payload['patient_Id'] ?? $patientId,
                'title_id'                  => $request->payload['title_id'] ?? optional($existingData)->title_id,
                'lastname'                  => ucwords($request->payload['lastname'] ?? optional($existingData)->lastname),
                'firstname'                 => ucwords($request->payload['firstname'] ?? optional($existingData)->firstname),
                'middlename'                => ucwords($request->payload['middlename'] ?? optional($existingData)->middlename),
                'suffix_id'                 => $request->payload['suffix_id'] ?? optional($existingData)->suffix_id,
                'birthdate'                 => $request->payload['birthdate'] ?? optional($existingData)->birthdate,
                'birthtime'                 => $request->payload['birthtime'] ?? optional($existingData)->birthtime,
                'birthplace'                => $request->payload['birthplace'] ?? optional($existingData)->birthplace,
                'age'                       => $request->payload['age'] ?? optional($existingData)->age,
                'sex_id'                    => $request->payload['sex_id'] ?? optional($existingData)->sex_id,
                'nationality_id'            => $request->payload['nationality_id'] ?? optional($existingData)->nationality_id,
                'citizenship_id'            => $request->payload['citizenship_id'] ?? optional($existingData)->citizenship_id,
                'complexion'                => $request->payload['complexion'] ?? optional($existingData)->complexion,
                'haircolor'                 => $request->payload['haircolor'] ?? optional($existingData)->haircolor,
                'eyecolor'                  => $request->payload['eyecolor'] ?? optional($existingData)->eyecolor,
                'height'                    => $request->payload['height'] ?? optional($existingData)->height,
                'weight'                    => $request->payload['weight'] ?? optional($existingData)->weight,
                'religion_id'               => $request->payload['religion_id'] ?? optional($existingData)->religion_id,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? optional($existingData)->civilstatus_id,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? optional($existingData)->bloodtype_id,
                'dialect_spoken'            => $request->payload['dialect_spoken'] ?? optional($existingData)->dialect_spoken,
                'bldgstreet'                => $request->payload['address']['bldgstreet'] ?? optional($existingData)->bldgstreet,
                'region_id'                 => $request->payload['address']['region_id'] ?? optional($existingData)->region_id,
                'province_id'               => $request->payload['address']['province_id'] ?? optional($existingData)->province_id,
                'municipality_id'           => $request->payload['address']['municipality_id'] ?? optional($existingData)->municipality_id,
                'barangay_id'               => $request->payload['address']['barangay_id'] ?? optional($existingData)->barangay_id,
                'country_id'                => $request->payload['address']['country_id'] ?? optional($existingData)->country_id,
                'zipcode_id'                => $request->payload['zipcode_id'] ?? optional($existingData)->zipcode_id,
                'occupation'                => $request->payload['occupation'] ?? optional($existingData)->occupation,
                'dependents'                => $request->payload['dependents'] ?? optional($existingData)->dependents,
                'telephone_number'          => $request->payload['telephone_number'] ?? optional($existingData)->telephone_number,
                'mobile_number'             => $request->payload['mobile_number'] ?? optional($existingData)->mobile_number,
                'email_address'             => $request->payload['email_address'] ?? optional($existingData)->email_address,
                'isSeniorCitizen'           => $request->payload['isSeniorCitizen'] ?? optional($existingData)->isSeniorCitizen ?? false,
                'SeniorCitizen_ID_Number'   => $request->payload['SeniorCitizen_ID_Number'] ?? optional($existingData)->SeniorCitizen_ID_Number,
                'isPWD'                     => $request->payload['isPWD'] ?? optional($existingData)->isPWD ?? false,
                'PWD_ID_Number'             => $request->payload['PWD_ID_Number'] ?? optional($existingData)->PWD_ID_Number,
                'isPhilhealth_Member'       => $request->payload['isPhilhealth_Member'] ?? optional($existingData)->isPhilhealth_Member ?? false,
                'Philhealth_Number'         => $request->payload['Philhealth_Number'] ?? optional($existingData)->Philhealth_Number,
                'isEmployee'                => $request->payload['isEmployee'] ?? optional($existingData)->isEmployee ?? false,
                'GSIS_Number'               => $request->payload['GSIS_Number'] ?? optional($existingData)->GSIS_Number,
                'SSS_Number'                => $request->payload['SSS_Number'] ?? optional($existingData)->SSS_Number,
                'passport_number'           => $request->payload['passport_number'] ?? optional($existingData)->passport_number,
                'seaman_book_number'        => $request->payload['seaman_book_number'] ?? optional($existingData)->seaman_book_number,
                'embarked_date'             => $request->payload['embarked_date'] ?? optional($existingData)->embarked_date,
                'disembarked_date'          => $request->payload['disembarked_date'] ?? optional($existingData)->disembarked_date,
                'xray_number'               => $request->payload['xray_number'] ?? optional($existingData)->xray_number,
                'ultrasound_number'         => $request->payload['ultrasound_number'] ?? optional($existingData)->ultrasound_number,
                'ct_number'                 => $request->payload['ct_number'] ?? optional($existingData)->ct_number,
                'petct_number'              => $request->payload['petct_number'] ?? optional($existingData)->petct_number,
                'mri_number'                => $request->payload['mri_number'] ?? optional($existingData)->mri_number,
                'mammo_number'              => $request->payload['mammo_number'] ?? optional($existingData)->mammo_number,
                'OB_number'                 => $request->payload['OB_number'] ?? optional($existingData)->OB_number,
                'nuclearmed_number'         => $request->payload['nuclearmed_number'] ?? optional($existingData)->nuclearmed_number,
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? optional($existingData)->typeofdeath_id,
                'dateofdeath'               => $request->payload['dateofdeath'] ?? optional($existingData)->dateofdeath,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? optional($existingData)->timeofdeath,
                'spDateMarried'             => $request->payload['spDateMarried'] ?? optional($existingData)->spDateMarried,
                'spLastname'                => $request->payload['spLastname'] ?? optional($existingData)->spLastname,
                'spFirstname'               => $request->payload['spFirstname'] ?? optional($existingData)->spFirstname,
                'spMiddlename'              => $request->payload['spMiddlename'] ?? optional($existingData)->spMiddlename,
                'spSuffix_id'               => $request->payload['spSuffix_id'] ?? optional($existingData)->spSuffix_id,
                'spAddress'                 => $request->payload['spAddress'] ?? optional($existingData)->spAddress,
                'sptelephone_number'        => $request->payload['sptelephone_number'] ?? optional($existingData)->sptelephone_number,
                'spmobile_number'           => $request->payload['spmobile_number'] ?? optional($existingData)->spmobile_number,
                'spOccupation'              => $request->payload['spOccupation'] ?? optional($existingData)->spOccupation,
                'spBirthdate'               => $request->payload['spBirthdate'] ?? optional($existingData)->spBirthdate,
                'spAge'                     => $request->payload['spAge'] ?? optional($existingData)->spAge,
                'motherLastname'            => $request->payload['motherLastname'] ?? optional($existingData)->motherLastname,
                'motherFirstname'           => $request->payload['motherFirstname'] ?? optional($existingData)->motherFirstname,
                'motherMiddlename'          => $request->payload['motherMiddlename'] ?? optional($existingData)->motherMiddlename,
                'motherSuffix_id'           => $request->payload['motherSuffix_id'] ?? optional($existingData)->motherSuffix_id,
                'motherAddress'             => $request->payload['motherAddress'] ?? optional($existingData)->motherAddress,
                'mothertelephone_number'    => $request->payload['mothertelephone_number'] ?? optional($existingData)->mothertelephone_number,
                'mothermobile_number'       => $request->payload['mothermobile_number'] ?? optional($existingData)->mothermobile_number,
                'motherOccupation'          => $request->payload['motherOccupation'] ?? optional($existingData)->motherOccupation, 
                'motherBirthdate'           => $request->payload['motherBirthdate'] ?? optional($existingData)->motherBirthdate,
                'motherAge'                 => $request->payload['motherAge'] ?? optional($existingData)->motherAge,
                'fatherLastname'            => $request->payload['fatherLastname'] ?? optional($existingData)->fatherLastname,
                'fatherFirstname'           => $request->payload['fatherFirstname'] ?? optional($existingData)->fatherFirstname,
                'fatherMiddlename'          => $request->payload['fatherMiddlename'] ?? optional($existingData)->fatherMiddlename,
                'fatherSuffix_id'           => $request->payload['fatherSuffix_id'] ?? optional($existingData)->fatherSuffix_id,
                'fatherAddress'             => $request->payload['fatherAddress'] ?? optional($existingData)->fatherAddress,
                'fathertelephone_number'    => $request->payload['fathertelephone_number'] ?? optional($existingData)->fathertelephone_number,
                'fathermobile_number'       => $request->payload['fathermobile_number'] ?? optional($existingData)->fathermobile_number,
                'fatherOccupation'          => $request->payload['fatherOccupation'] ?? optional($existingData)->fatherOccupation,
                'fatherBirthdate'           => $request->payload['fatherBirthdate'] ?? optional($existingData)->fatherBirthdate,
                'fatherAge'                 => $request->payload['fatherAge'] ?? optional($existingData)->fatherAge,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? optional($existingData)->portal_access_uid,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? optional($existingData)->portal_access_pwd,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? optional($existingData)->isBlacklisted,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? optional($existingData)->blacklist_reason,
                'isAbscond'                 => $request->payload['isAbscond'] ?? optional($existingData)->isAbscond ?? false,
                'abscond_details'           => $request->payload['abscond_details'] ?? optional($existingData)->abscond_details,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? optional($existingData)->is_old_patient,
                'patient_picture'           => $request->payload['patient_picture'] ?? optional($existingData)->patient_picture,
                'patient_picture_path'      => $request->payload['patient_picture_path'] ?? optional($existingData)->patient_picture_path,
                'branch_id'                 => $request->payload['branch_id'] ?? optional($existingData)->branch_id,
                'previous_patient_id'       => $request->payload['previous_patient_id'] ?? optional($existingData)->previous_patient_id,
                'medsys_patient_id'         => $request->payload['medsys_patient_id'] ?? optional($existingData)->medsys_patient_id,
                'createdBy'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => $currentTimestamp,
                'updatedBy'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'updated_at'                => $currentTimestamp,   
            ];
        }

        public function preparePatientRegistryData($request, $checkUser, $patient_id, $registry_id, $er_Case_No, $existingData=null) {
            $identifier = $this->preparepatientIdentifierData($request);
            return [
                'branch_Id'                                 =>  1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'er_Case_No'                                => $er_Case_No,
                'register_source'                           => $request->payload['register_Source'] ?? null,
                'register_Casetype'                         => $request->payload['register_Casetype'] ?? null,
                'register_Link_Case_No'                     => $request->payload['register_Link_Case_No'] ?? null,
                'register_Case_No_Consolidate'              => $request->payload['register_Case_No_Consolidate'] ?? null,
                'patient_Age'                               => $request->payload['age'] ?? null,
                'er_Bedno'                                  => $request->payload['er_Bedno'] ?? null,
                'room_Code'                                 => $request->payload['room_Code'] ?? null,
                'room_Rate'                                 => $request->payload['room_Rate'] ?? null,
                'mscAccount_Type'                           => $request->payload['mscAccount_Type'] ?? '',
                'mscAccount_Discount_Id'                    => $request->payload['mscAccount_Discount_Id'] ?? null,
                'mscAccount_Trans_Types'                    => $request->payload['mscAccount_Trans_Types'] ?? null, 
                'mscAdmission_Type_Id'                      => $request->payload['mscAdmission_Type_Id'] ?? null,
                'mscPatient_Category'                       => isset($request->payload['patient_Id']) ? 3 : 2,
                'mscPrice_Groups'                           => $request->payload['mscPrice_Groups'] ?? null,
                'mscPrice_Schemes'                          => $request->payload['mscPrice_Schemes'] ?? 100,
                'mscService_Type'                           => $request->payload['mscService_Type'] ?? null,
                'mscService_Type2'                          => $request->payload['mscService_Type2'] ?? null,
                'mscDiet_Meal_Id'                           => $request->payload['mscDiet_Meal_Id'] ?? null,
                'mscDisposition_Id'                         => $request->payload['mscDisposition_Id'] ?? null,
                'mscTriage_level_Id'                        => $request->payload['mscTriage_level_Id'] ?? null,
                'mscCase_Result_Id'                         => $request->payload['mscCase_Result_Id'] ?? null,
                'mscCase_Indicators_Id'                     => $request->payload['mscCase_Indicators_Id'] ?? null,
                'mscPrivileged_Card_Id'                     => $request->payload['mscPrivileged_Card_Id'] ?? null,
                'mscBroughtBy_Relationship_Id'              => $request->payload['mscBroughtBy_Relationship_Id'] ?? null,
                'queue_Number'                              => $request->payload['queue_Number'] ?? null,
                'arrived_Date'                              => Carbon::now(),
                'registry_Userid'                           => isset($checkUser->idnumber) 
                                                            ?  $checkUser->idnumber
                                                            :  Auth()->user()->idnumber,
                'registry_Date'                             => Carbon::now(),
                'registry_Status'                           => $request->payload['registry_Status'] ?? 1,
                'registry_Hostname'                         => (new GetIP())->getHostname(),
                'discharged_Userid'                         => $request->payload['discharged_Userid'] ?? null,
                'discharged_Date'                           => $request->payload['discharged_Date'] ?? null,
                'discharged_Hostname'                       => $request->payload['discharged_Hostname'] ?? null,
                'billed_Userid'                             => $request->payload['billed_Userid'] ?? null,
                'billed_Date'                               => $request->payload['billed_Date'] ?? null,
                'billed_Remarks'                            => $request->payload['billed_Remarks'] ?? null,
                'billed_Hostname'                           => $request->payload['billed_Hostname'] ?? null,
                'mgh_Userid'                                => $request->payload['mgh_Userid'] ?? null,
                'mgh_Datetime'                              => $request->payload['mgh_Datetime'] ?? null,
                'mgh_Hostname'                              => $request->payload['mgh_Hostname'] ?? null,
                'untag_Mgh_Userid'                          => $request->payload['untag_Mgh_Userid'] ?? null,
                'untag_Mgh_Datetime'                        => $request->payload['untag_Mgh_Datetime'] ?? null,
                'untag_Mgh_Hostname'                        => $request->payload['untag_Mgh_Hostname'] ?? null,
                'isHoldReg'                                 => $request->payload['isHoldReg'] ?? false,
                'hold_Userid'                               => $request->payload['hold_Userid'] ?? null,
                'hold_No'                                   => $request->payload['hold_No'] ?? null,
                'hold_Date'                                 => $request->payload['hold_Date'] ?? null,
                'hold_Remarks'                              => $request->payload['hold_Remarks'] ?? null,
                'hold_Hostname'                             => $request->payload['hold_Hostname'] ?? null,
                'isRevoked'                                 => $request->payload['isRevoked'] ?? false,
                'revokedBy'                                 => $request->payload['revokedBy'] ?? null,
                'revoked_Date'                              => $request->payload['revoked_Date'] ?? null,
                'revoked_Remarks'                           => $request->payload['revoked_Remarks'] ?? null,
                'revoked_Hostname'                          => $request->payload['revoked_Hostname'] ?? null,
                'dischargeNotice_Userid'                    => $request->payload['dischargeNotice_Userid'] ?? null,
                'dischargeNotice_Date'                      => $request->payload['dischargeNotice_Date'] ?? null,
                'dischargeNotice_Hostname'                  => $request->payload['dischargeNotice_Hostname'] ?? null,
                'hbps_PrintedBy'                            => $request->payload['hbps_PrintedBy'] ?? null,
                'hbps_Date'                                 => $request->payload['hbps_Date'] ?? null,
                'hbps_Hostname'                             => $request->payload['hbps_Hostname'] ?? null,
                'informant_Lastname'                        => $request->payload['informant_Lastname'] ?? null,
                'informant_Firstname'                       => $request->payload['informant_Firstname'] ?? null,
                'informant_Middlename'                      => $request->payload['informant_Middlename'] ?? null,
                'informant_Suffix'                          => $request->payload['informant_Suffix'] ?? null,
                'informant_Address'                         => $request->payload['informant_Address'] ?? null,
                'informant_Relation_id'                     => $request->payload['informant_Relation_id'] ?? null,
                'guarantor_Id'                              => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patient_id,
                'guarantor_Name'                            => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? 'Self Pay',
                'guarantor_Approval_code'                   => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? null,
                'guarantor_Approval_no'                     => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? null,
                'guarantor_Approval_date'                   => isset($request->payload['selectedGuarantor'][0]['guarantor_Approval_date']) 
                                                                && Carbon::hasFormat($request->payload['selectedGuarantor'][0]['guarantor_Approval_date'], 'Y-m-d')
                                                                ? Carbon::parse($request->payload['selectedGuarantor'][0]['guarantor_Approval_date']) 
                                                                : null,
                'guarantor_Validity_date'                   => isset($request->payload['selectedGuarantor'][0]['guarantor_Validity_date']) 
                                                                && Carbon::hasFormat($request->payload['selectedGuarantor'][0]['guarantor_Validity_date'], 'Y-m-d')
                                                                ? Carbon::parse($request->payload['selectedGuarantor'][0]['guarantor_Validity_date']) 
                                                                : null,
                'guarantor_Approval_remarks'                => $request->payload['guarantor_Approval_remarks'] ?? null,
                'isWithCreditLimit'                         => isset($request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit']) ? true : false,
                'guarantor_Credit_Limit'                    => $request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? null,
                'isWithMultiple_Gurantor'                   => $request->payload['isWithMultiple_Gurantor'] ?? false,
                'gurantor_Mutiple_TotalCreditLimit'         => $request->payload['gurantor_Mutiple_TotalCreditLimit'] ?? false,
                'isWithPhilHealth'                          => $request->payload['isWithPhilHealth'] ?? false,
                'mscPHIC_Membership_Type_id'                => $request->payload['mscPHIC_Membership_Type_id'] ?? null,
                'philhealth_Number'                         => $request->payload['philhealth_Number'] ?? null,
                'isWithMedicalPackage'                      => $request->payload['isWithMedicalPackage'] ?? false,
                'medical_Package_Id'                        => $request->payload['medical_Package_Id'] ?? null,
                'medical_Package_Name'                      => $request->payload['medical_Package_Name'] ?? null,
                'medical_Package_Amount'                    => $request->payload['medical_Package_Amount'] ?? null,
                'chief_Complaint_Description'               => $request->payload['chief_Complaint_Description'] ?? null,
                'impression'                                => $request->payload['impression'] ?? null,
                'admitting_Diagnosis'                       => $request->payload['admitting_Diagnosis'] ?? null,
                'discharge_Diagnosis'                       => $request->payload['discharge_Diagnosis'] ?? null,
                'preOperative_Diagnosis'                    => $request->payload['preOperative_Diagnosis'] ?? null,
                'postOperative_Diagnosis'                   => $request->payload['postOperative_Diagnosis'] ?? null,
                'surgical_Procedure'                        => $request->payload['surgical_Procedure'] ?? null,
                'triageNotes'                               => $request->payload['triageNotes'] ?? null,
                'triageDate'                                => $request->payload['triageDate'] ?? null,
                'isCriticallyIll'                           => $request->payload['isCriticallyIll'] ?? false,
                'illness_Type'                              => $request->payload['illness_Type'] ?? null,
                'attending_Doctor'                          => $request->payload['selectedConsultant'][0]['attending_Doctor'] ?? null,
                'attending_Doctor_fullname'                 => $request->payload['selectedConsultant'][0]['attending_Doctor_fullname'] ?? null,
                'bmi'                                       => $request->payload['bmi'] ?? null,
                'weight'                                    => $request->payload['weight'] ?? null,
                'weightUnit'                                => $request->payload['weightUnit'] ?? null,
                'height'                                    => $request->payload['height'] ?? null,
                'heightUnit'                                => $request->payload['heightUnit'] ?? null,
                'bloodPressureSystolic'                     => $request->payload['bloodPressureSystolic'] ?? null,
                'bloodPressureDiastolic'                    => $request->payload['bloodPressureDiastolic'] ?? null,
                'temperatute'                               => $request->payload['temperatute'] ?? null,
                'pulseRate'                                 => $request->payload['pulseRate'] ?? null,
                'respiratoryRate'                           => $request->payload['respiratoryRate'] ?? null,
                'oxygenSaturation'                          => $request->payload['oxygenSaturation'] ?? null,
                'isHemodialysis'                            => $identifier['isHemodialysis'] ?? false,
                'isPeritoneal'                              => $identifier['isPeritoneal'] ?? false,
                'isLINAC'                                   => $identifier['isLINAC'] ?? false,
                'isCOBALT'                                  => $identifier['isCOBALT'] ?? false,
                'isBloodTrans'                              => $identifier['isBloodTrans'] ?? false,
                'isChemotherapy'                            => $identifier['isChemotherapy'] ?? false,
                'isBrachytherapy'                           => $identifier['isBrachytherapy'] ?? false,
                'isDebridement'                             => $identifier['isDebridement'] ?? false,
                'isTBDots'                                  => $identifier['isTBDots'] ?? false,
                'isPAD'                                     => $identifier['isPAD'] ?? false,
                'isRadioTherapy'                            => $identifier['isRadioTherapy'],
                'typeOfBirth_id'                            => $request->payload['typeOfBirth_id'] ?? null,
                'isWithBaby'                                => $request->payload['isWithBaby'] ?? null,
                'isRoomIn'                                  => $request->payload['isRoomIn'] ?? null,
                'birthDate'                                 => $request->payload['birthDate'] ?? null,
                'birthTime'                                 => $request->payload['birthTime'] ?? null,
                'newborn_Status_Id'                         => $request->payload['newborn_Status_Id'] ?? null,
                'mother_Case_No'                            => $request->payload['mother_Case_No'] ?? null,
                'isDiedLess48Hours'                         => $request->payload['isDiedLess48Hours'] ?? null,
                'isDeadOnArrival'                           => $request->payload['isDeadOnArrival'] ?? null,
                'isAutopsy'                                 => $request->payload['isAutopsy'] ?? null,
                'typeOfDeath_id'                            => $request->payload['typeOfDeath_id'] ?? null,
                'dateOfDeath'                               => $request->payload['dateOfDeath'] ?? null,
                'timeOfDeath'                               => $request->payload['timeOfDeath'] ?? null,
                'barcode_Image'                             => $request->payload['barcode_Image'] ?? null,
                'barcode_Code_Id'                           => $request->payload['barcode_Code_Id'] ?? null,
                'barcode_Code_String'                       => $request->payload['barcode_Code_String'] ?? null,
                'isreferredFrom'                            => $request->payload['isreferredFrom'] ?? false,
                'referred_From_HCI'                         => $request->payload['referred_From_HCI'] ?? null,
                'referred_From_HCI_address'                 => $request->payload['FromHCIAddress'] ?? null,
                'referred_From_HCI_code'                    => $request->payload['referred_From_HCI_code'] ?? null,
                'referred_To_HCI'                           => $request->payload['referred_To_HCI'] ?? null,
                'referred_To_HCI_code'                      => $request->payload['referred_To_HCI_code'] ?? null,
                'referred_To_HCI_address'                   => $request->payload['ToHCIAddress'] ?? null,
                'referring_Doctor'                          => $request->payload['referring_Doctor'] ?? null,
                'referral_Reason'                           => $request->payload['referral_Reason'] ?? null,
                'isWithConsent_DPA'                         => $request->payload['isWithConsent_DPA'] ?? null,
                'isConfidentialPatient'                     => $request->payload['isConfidentialPatient'] ?? null,
                'isMedicoLegal'                             => $request->payload['isMedicoLegal'] ?? null,
                'isFinalBill'                               => $request->payload['isFinalBill'] ?? null,
                'isWithPromissoryNote'                      => $request->payload['isWithPromissoryNote'] ?? null,
                'isFirstNotice'                             => $request->payload['isFirstNotice'] ?? null,
                'FirstNoteDate'                             => $request->payload['FirstNoteDate'] ?? null,
                'isSecondNotice'                            => $request->payload['isSecondNotice'] ?? null,
                'SecondNoticeDate'                          => $request->payload['SecondNoticeDate'] ?? null,
                'isFinalNotice'                             => $request->payload['isFinalNotice'] ?? null,
                'FinalNoticeDate'                           => $request->payload['FinalNoticeDate'] ?? null,
                'isOpenLateCharges'                         => $request->payload['isOpenLateCharges'] ?? null,
                'isBadDebt'                                 => $request->payload['isBadDebt'] ?? null,
                'registry_Remarks'                          => $request->payload['registry_Remarks'] ?? null,
                'medsys_map_idnum'                          => $request->payload['medsys_map_idnum'] ?? null,
                'createdBy'                                 => isset($checkUser->idnumber) 
                                                            ?  $checkUser->idnumber
                                                            :  Auth()->user()->idnumber,
                'created_at'                                => Carbon::now(), 
            ];
    }

        public function prepareOBGHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                                            => $patient_id,
                'case_No'                                               => $registry_id,
                'obsteric_Code'                                         => $request->payload['obsteric_Code'] ?? null,
                'menarchAge'                                            => $request->payload['menarchAge'] ?? null,
                'menopauseAge'                                          => $request->payload['menopauseAge'] ?? null,
                'cycleLength'                                           => $request->payload['cycleLength'] ?? null,
                'cycleRegularity'                                       => $request->payload['cycleRegularity'] ?? null,
                'lastMenstrualPeriod'                                   => $request->payload['lastMenstrualPeriod'] ?? null,
                'contraceptiveUse'                                      => $request->payload['contraceptiveUse'] ?? null,
                'lastPapSmearDate'                                      => $request->payload['lastPapSmearDate'] ?? null,
                'isVitalSigns_Normal'                                   => $request->payload['isVitalSigns_Normal'] ?? null,
                'isAscertainPresent_PregnancyisLowRisk'                 => $request->payload['isAscertainPresent_PregnancyisLowRisk'] ?? null,
                'riskfactor_MultiplePregnancy'                          => $request->payload['riskfactor_MultiplePregnancy'] ?? null,
                'riskfactor_OvarianCyst'                                => $request->payload['riskfactor_OvarianCyst'] ?? null,
                'riskfactor_MyomaUteri'                                 => $request->payload['riskfactor_MyomaUteri'] ?? null,
                'riskfactor_PlacentaPrevia'                             => $request->payload['riskfactor_PlacentaPrevia'] ?? null,
                'riskfactor_Historyof3Miscarriages'                     => $request->payload['riskfactor_Historyof3Miscarriages'] ?? null,
                'riskfactor_HistoryofStillbirth'                        => $request->payload['riskfactor_HistoryofStillbirth'] ?? null,
                'riskfactor_HistoryofEclampsia'                         => $request->payload['riskfactor_HistoryofEclampsia'] ?? null,
                'riskfactor_PrematureContraction'                       => $request->payload['riskfactor_PrematureContraction'] ?? null,
                'riskfactor_NotApplicableNone'                          => $request->payload['riskfactor_NotApplicableNone'] ?? null,
                'medicalSurgical_Hypertension'                          => $request->payload['medicalSurgical_Hypertension'] ?? null,
                'medicalSurgical_HeartDisease'                          => $request->payload['medicalSurgical_HeartDisease'] ?? null,
                'medicalSurgical_Diabetes'                              => $request->payload['medicalSurgical_Diabetes'] ?? null,
                'medicalSurgical_ThyroidDisorder'                       => $request->payload['medicalSurgical_ThyroidDisorder'] ?? null,
                'medicalSurgical_Obesity'                               => $request->payload['medicalSurgical_Obesity'] ?? null,
                'medicalSurgical_ModerateToSevereAsthma'                => $request->payload['medicalSurgical_ModerateToSevereAsthma'] ?? null,
                'medicalSurigcal_Epilepsy'                              => $request->payload['medicalSurigcal_Epilepsy'] ?? null,
                'medicalSurgical_RenalDisease'                          => $request->payload['medicalSurgical_RenalDisease'] ?? null,
                'medicalSurgical_BleedingDisorder'                      => $request->payload['medicalSurgical_BleedingDisorder'] ?? null,
                'medicalSurgical_HistoryOfPreviousCesarianSection'      => $request->payload['medicalSurgical_HistoryOfPreviousCesarianSection'] ?? null,
                'medicalSurgical_HistoryOfUterineMyomectomy'            => $request->payload['medicalSurgical_HistoryOfUterineMyomectomy'] ?? null,
                'medicalSurgical_NotApplicableNone'                     => $request->payload['medicalSurgical_NotApplicableNone'] ?? null,
                'deliveryPlan_OrientationToMCP'                         => $request->payload['deliveryPlan_OrientationToMCP'] ?? null,
                'deliveryPlan_ExpectedDeliveryDate'                     => $request->payload['deliveryPlan_ExpectedDeliveryDate'] ?? null,
                'followUp_Prenatal_ConsultationNo_2nd'                  => $request->payload['followUp_Prenatal_ConsultationNo_2nd'] ?? null,
                'followUp_Prenatal_DateVisit_2nd'                       => $request->payload['followUp_Prenatal_DateVisit_2nd'] ?? null,
                'followUp_Prenatal_AOGInWeeks_2nd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_2nd'] ?? null,
                'followUp_Prenatal_Weight_2nd'                          => $request->payload['followUp_Prenatal_Weight_2nd'] ?? null,
                'followUp_Prenatal_CardiacRate_2nd'                     => $request->payload['followUp_Prenatal_CardiacRate_2nd'] ?? null,
                'followUp_Prenatal_RespiratoryRate_2nd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_2nd'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_2nd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_2nd'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_2nd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_2nd'] ?? null,
                'followUp_Prenatal_Temperature_2nd'                     => $request->payload['followUp_Prenatal_Temperature_2nd'] ?? null,
                'followUp_Prenatal_ConsultationNo_3rd'                  => $request->payload['followUp_Prenatal_ConsultationNo_3rd'] ?? null,
                'followUp_Prenatal_DateVisit_3rd'                       => $request->payload['followUp_Prenatal_DateVisit_3rd'] ?? null,
                'followUp_Prenatal_AOGInWeeks_3rd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_3rd'] ?? null,
                'followUp_Prenatal_Weight_3rd'                          => $request->payload['followUp_Prenatal_Weight_3rd'] ?? null,
                'followUp_Prenatal_CardiacRate_3rd'                     => $request->payload['followUp_Prenatal_CardiacRate_3rd'] ?? null,
                'followUp_Prenatal_RespiratoryRate_3rd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_3rd'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_3rd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_3rd'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_3rd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_3rd'] ?? null,
                'followUp_Prenatal_Temperature_3rd'                     => $request->payload['followUp_Prenatal_Temperature_3rd'] ?? null,
                'followUp_Prenatal_ConsultationNo_4th'                  => $request->payload['followUp_Prenatal_ConsultationNo_4th'] ?? null,
                'followUp_Prenatal_DateVisit_4th'                       => $request->payload['followUp_Prenatal_DateVisit_4th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_4th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_4th'] ?? null,
                'followUp_Prenatal_Weight_4th'                          => $request->payload['followUp_Prenatal_Weight_4th'] ?? null,
                'followUp_Prenatal_CardiacRate_4th'                     => $request->payload['followUp_Prenatal_CardiacRate_4th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_4th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_4th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_4th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_4th'] ?? null,
                'followUp_Prenatal_ConsultationNo_5th'                  => $request->payload['followUp_Prenatal_ConsultationNo_5th'] ?? null,
                'followUp_Prenatal_DateVisit_5th'                       => $request->payload['followUp_Prenatal_DateVisit_5th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_5th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_5th'] ?? null,
                'followUp_Prenatal_Weight_5th'                          => $request->payload['followUp_Prenatal_Weight_5th'] ?? null,
                'followUp_Prenatal_CardiacRate_5th'                     => $request->payload['followUp_Prenatal_CardiacRate_5th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_5th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_5th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_5th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_5th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_5th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_5th'] ?? null,
                'followUp_Prenatal_Temperature_5th'                     => $request->payload['followUp_Prenatal_Temperature_5th'] ?? null,
                'followUp_Prenatal_ConsultationNo_6th'                  => $request->payload['followUp_Prenatal_ConsultationNo_6th'] ?? null,
                'followUp_Prenatal_DateVisit_6th'                       => $request->payload['followUp_Prenatal_DateVisit_6th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_6th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_6th'] ?? null,
                'followUp_Prenatal_Weight_6th'                          => $request->payload['followUp_Prenatal_Weight_6th'] ?? null,
                'followUp_Prenatal_CardiacRate_6th'                     => $request->payload['followUp_Prenatal_CardiacRate_6th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_6th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_6th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_6th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_6th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_6th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_6th'] ?? null,
                'followUp_Prenatal_Temperature_6th'                     => $request->payload['followUp_Prenatal_Temperature_6th'] ?? null,
                'followUp_Prenatal_ConsultationNo_7th'                  => $request->payload['followUp_Prenatal_ConsultationNo_7th'] ?? null,
                'followUp_Prenatal_DateVisit_7th'                       => $request->payload['followUp_Prenatal_DateVisit_7th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_7th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_7th'] ?? null,
                'followUp_Prenatal_Weight_7th'                          => $request->payload['followUp_Prenatal_Weight_7th'] ?? null,
                'followUp_Prenatal_CardiacRate_7th'                     => $request->payload['followUp_Prenatal_CardiacRate_7th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_7th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_7th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_7th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_7th'] ?? null,
                'followUp_Prenatal_Temperature_7th'                     => $request->payload['followUp_Prenatal_Temperature_7th'] ?? null,
                'followUp_Prenatal_ConsultationNo_8th'                  => $request->payload['followUp_Prenatal_ConsultationNo_8th'] ?? null,
                'followUp_Prenatal_DateVisit_8th'                       => $request->payload['followUp_Prenatal_DateVisit_8th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_8th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_8th'] ?? null,
                'followUp_Prenatal_Weight_8th'                          => $request->payload['followUp_Prenatal_Weight_8th'] ?? null,
                'followUp_Prenatal_CardiacRate_8th'                     => $request->payload['followUp_Prenatal_CardiacRate_8th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_8th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_8th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_8th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_8th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_8th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_8th'] ?? null,
                'followUp_Prenatal_Temperature_8th'                     => $request->payload['followUp_Prenatal_Temperature_8th'] ?? null,
                'followUp_Prenatal_ConsultationNo_9th'                  => $request->payload['followUp_Prenatal_ConsultationNo_9th'] ?? null,
                'followUp_Prenatal_DateVisit_9th'                       => $request->payload['followUp_Prenatal_DateVisit_9th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_9th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_9th'] ?? null,
                'followUp_Prenatal_Weight_9th'                          => $request->payload['followUp_Prenatal_Weight_9th'] ?? null,
                'followUp_Prenatal_CardiacRate_9th'                     => $request->payload['followUp_Prenatal_CardiacRate_9th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_9th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_9th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_9th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_9th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_9th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_9th'] ?? null,
                'followUp_Prenatal_Temperature_9th'                     => $request->payload['followUp_Prenatal_Temperature_9th'] ?? null,
                'followUp_Prenatal_ConsultationNo_10th'                 => $request->payload['followUp_Prenatal_ConsultationNo_10th'] ?? null,
                'followUp_Prenatal_DateVisit_10th'                      => $request->payload['followUp_Prenatal_DateVisit_10th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_10th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_10th'] ?? null,
                'followUp_Prenatal_Weight_10th'                         => $request->payload['followUp_Prenatal_Weight_10th'] ?? null,
                'followUp_Prenatal_CardiacRate_10th'                    => $request->payload['followUp_Prenatal_CardiacRate_10th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_10th'                => $request->payload['followUp_Prenatal_RespiratoryRate_10th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_10th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_10th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_10th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_10th'] ?? null,
                'followUp_Prenatal_Temperature_10th'                    => $request->payload['followUp_Prenatal_Temperature_10th'] ?? null,
                'followUp_Prenatal_ConsultationNo_11th'                 => $request->payload['followUp_Prenatal_ConsultationNo_11th'] ?? null,
                'followUp_Prenatal_DateVisit_11th'                      => $request->payload['followUp_Prenatal_DateVisit_11th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_11th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_11th'] ?? null,
                'followUp_Prenatal_Weight_11th'                         => $request->payload['followUp_Prenatal_Weight_11th'] ?? null,
                'followUp_Prenatal_CardiacRate_11th'                    => $request->payload['followUp_Prenatal_CardiacRate_11th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_11th'                => $request->payload['followUp_Prenatal_RespiratoryRate_11th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_11th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_11th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_11th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_11th'] ?? null,
                'followUp_Prenatal_Temperature_11th'                    => $request->payload['followUp_Prenatal_Temperature_11th'] ?? null,
                'followUp_Prenatal_ConsultationNo_12th'                 => $request->payload['followUp_Prenatal_ConsultationNo_12th'] ?? null,
                'followUp_Prenatal_DateVisit_12th'                      => $request->payload['followUp_Prenatal_DateVisit_12th'] ?? null,
                'followUp_Prenatal_AOGInWeeks_12th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_12th'] ?? null,
                'followUp_Prenatal_Weight_12th'                         => $request->payload['ffollowUp_Prenatal_Weight_12th'] ?? null,
                'followUp_Prenatal_CardiacRate_12th'                    => $request->payload['followUp_Prenatal_CardiacRate_12th'] ?? null,
                'followUp_Prenatal_RespiratoryRate_12th'                => $request->payload['followUp_Prenatal_RespiratoryRate_12th'] ?? null,
                'followUp_Prenatal_BloodPresureSystolic_12th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_12th'] ?? null,
                'followUp_Prenatal_BloodPresureDiastolic_12th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_12th'] ?? null,
                'followUp_Prenatal_Temperature_12th'                    => $request->payload['followUp_Prenatal_Temperature_12th'] ?? null,
                'followUp_Prenatal_Remarks'                             => $request->payload['followUp_Prenatal_Remarks'] ?? null,
                'createdby'                                             => isset($checkUser->idnumber) 
                                                                        ?  $checkUser->idnumber
                                                                        :  Auth()->user()->idnumber,
                'created_at'                                            => Carbon::now(),
            ];
        }

        public function  preparePastImmunizationData($request, $checkUser, $patient_id, $existingData = null) {
            return [
                'branch_Id'             => 1,
                'patient_Id'            => $patient_id,
                'vaccine_Id'            => 0,
                'administration_Date'   => $request->payload['administration_Date'] ?? null,
                'dose'                  => $request->payload['dose'] ?? null,
                'site'                  => $request->payload['site'] ?? null,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? null,
                'notes'                 => $request->payload['notes'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];
        }

        public function preparePastMedicalHistoryData($request, $checkUser, $patient_id, $existingData = null) { 
            return [
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? null,
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? null,
                'treament'                  => $request->payload['treament'] ?? null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }

        public function preparePastMedicalProcedureData($request, $checkUser, $patient_id, $existingData = null) {
            return [
                'patient_Id'                => $patient_id,
                'description'               => $request->payload['description'] ?? null,
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ?? null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }

        public function prepareAdministeredMedicineData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'transactDate'          => Carbon::now(),
                'item_Id'               => $request->payload['item_Id'] ?? null,
                'quantity'              => $request->payload['quantity'] ?? null,
                'administered_Date'     => $request->payload['administered_Date'] ?? null,
                'administered_By'       => $request->payload['administered_By'] ?? null,
                'reference_num'         => $request->payload['reference_num'] ?? null,
                'transaction_num'       => $request->payload['transaction_num'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];
        }

        public function prepareHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'branch_Id'                                 => $request->payload['branch_Id'] ?? 1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'brief_History'                             => $request->payload['brief_History'] ?? null,
                'pastMedical_History'                       => $request->payload['pastMedical_History'] ?? null,
                'family_History'                            => $request->payload['family_History'] ?? null,
                'personalSocial_History'                    => $request->payload['personalSocial_History'] ?? null,
                'chief_Complaint_Description'               => $complaint ?? null,
                'impression'                                => $request->payload['impression'] ?? null,
                'admitting_Diagnosis'                       => $request->payload['admitting_Diagnosis'] ?? null,
                'discharge_Diagnosis'                       => $request->payload['discharge_Diagnosis'] ?? null,
                'preOperative_Diagnosis'                    => $request->payload['preOperative_Diagnosis'] ?? null,
                'postOperative_Diagnosis'                   => $request->payload['postOperative_Diagnosis'] ?? null,
                'surgical_Procedure'                        => $request->payload['surgical_Procedure'] ?? null,
                'physicalExamination_Skin'                  => $request->payload['physicalExamination_Skin'] ?? null,
                'physicalExamination_HeadEyesEarsNeck'      => $request->payload['physicalExamination_HeadEyesEarsNeck'] ?? null,
                'physicalExamination_Neck'                  => $request->payload['physicalExamination_Neck'] ?? null,
                'physicalExamination_ChestLungs'            => $request->payload['physicalExamination_ChestLungs'] ?? null,
                'physicalExamination_CardioVascularSystem'  => $request->payload['physicalExamination_CardioVascularSystem'] ?? null,
                'physicalExamination_Abdomen'               => $request->payload['physicalExamination_Abdomen'] ?? null,
                'physicalExamination_GenitourinaryTract'    => $request->payload['physicalExamination_GenitourinaryTract'] ?? null,
                'physicalExamination_Rectal'                => $request->payload['physicalExamination_Rectal'] ?? null,
                'physicalExamination_Musculoskeletal'       => $request->payload['physicalExamination_Musculoskeletal'] ?? null,
                'physicalExamination_LympNodes'             => $request->payload['physicalExamination_LympNodes'] ?? null,
                'physicalExamination_Extremities'           => $request->payload['physicalExamination_Extremities'] ?? null,
                'physicalExamination_Neurological'          => $request->payload['physicalExamination_Neurological'] ?? null,
                'createdby'                                 => isset($checkUser->idnumber) 
                                                            ?  $checkUser->idnumber
                                                            :  Auth()->user()->idnumber,
                'created_at'                                => Carbon::now(),
            ];
        }

        public function preparePatientImmunizationData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'branch_id'             => 1,
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? 1,
                'administration_Date'   => $request->payload['administration_Date'] ?? null,
                'dose'                  => $request->payload['dose'] ?? null,
                'site'                  => $request->payload['site'] ?? null,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? null,
                'Notes'                 => $request->payload['Notes'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];
        }

        public function preparePatientMedicalProcedure($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => $request->payload['description'] ?? null,
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? null,
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? null,
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? null,
                'createdby'                     => isset($checkUser->idnumber) 
                                                ?  $checkUser->idnumber
                                                :  Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];
        }

        public function prepareVitalSignsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'transDate'                 => Carbon::now(),
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic'])  ? (int)$request->payload['bloodPressureSystolic'] :   null,
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : null,
                'temperature'               => isset($request->payload['temperatue']) ? (int)$request->payload['temperatue'] : null,
                'pulseRate'                 => isset($request->payload['pulseRate'])  ? (int)$request->payload['pulseRate'] : null,
                'respiratoryRate'           => isset($request->payload['respiratoryRate'])  ? (int)$request->payload['respiratoryRate'] : null,
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }

        public function prepareBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'    => $patient_id,
                'case_No'       => $registry_id,
                'description'   => $request->payload['description'] ?? null,
                'createdby'     => isset($checkUser->idnumber) 
                                ?  $checkUser->idnumber
                                :  Auth()->user()->idnumber,
                'created_at'    => Carbon::now(),
            ];
        }

        public function preparePastBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'    => $patient_id,
                'description'   => $request->payload['description'] ?? null,
                'createdby'     => isset($checkUser->idnumber) 
                                ?  $checkUser->idnumber
                                :  Auth()->user()->idnumber,
                'created_at'    => Carbon::now(),
                'updatedby'     => isset($checkUser->idnumber) 
                                ?  $checkUser->idnumber
                                :  Auth()->user()->idnumber,
                'updated_at'    => Carbon::now()
            ];
        }

        public function preparePatientDoctorsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            $consultant = $request->payload['selectedConsultant'][0] ?? null;
            return [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => isset($consultant['attending_Doctor']) ? $consultant['attending_Doctor'] : null,
                'doctors_Fullname'  => isset($consultant['attending_Doctor_fullname']) ? $consultant['attending_Doctor_fullname'] : null,
                'role_Id'           => isset($consultant['Doctors_Role_Id']) ? $consultant['Doctors_Role_Id'] : null,
                'specialization_id' => isset($consultant['Doctors_Specialization_Id']) ? $consultant['Doctors_Specialization_Id'] : null,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];
        }

        public function preparePatientPhysicalAbdomenData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? null,
                'palpable_Masses'           => $request->payload['palpable_Masses'] ?? null,
                'abdominal_Rigidity'        => $request->payload['abdominal_Rigidity'] ?? null,
                'uterine_Contraction'       => $request->payload['uterine_Contraction'] ?? null,
                'hyperactive_Bowel_Sounds'  => $request->payload['hyperactive_Bowel_Sounds'] ?? null,
                'others_Description'        => $request->payload['others_Description'] ?? null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }

        public function preparePatientPertinentSignAndSymptomsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'altered_Mental_Sensorium'          => $request->payload['altered_Mental_Sensorium'] ?? null,
                'abdominal_CrampPain'               => $request->payload['abdominal_CrampPain'] ?? null,
                'anorexia'                          => $request->payload['anorexia'] ?? null,
                'bleeding_Gums'                     => $request->payload['bleeding_Gums'] ?? null,
                'body_Weakness'                     => $request->payload['body_Weakness'] ?? null,
                'blurring_Of_Vision'                => $request->payload['blurring_Of_Vision'] ?? null,
                'chest_PainDiscomfort'              => $request->payload['chest_PainDiscomfort'] ?? null,
                'constipation'                      => $request->payload['constipation'] ?? null,
                'cough'                             => $request->payload['cough'] ?? null,
                'diarrhea'                          => $request->payload['diarrhea'] ?? null,
                'dizziness'                         => $request->payload['dizziness'] ?? null,
                'dysphagia'                         => $request->payload['dysphagia'] ?? null,
                'dysuria'                           => $request->payload['dysuria'] ?? null,
                'epistaxis'                         => $request->payload['epistaxis'] ?? null,
                'fever'                             => $request->payload['fever'] ?? null,
                'frequency_Of_Urination'            => $request->payload['frequency_Of_Urination'] ?? null,
                'headache'                          => $request->payload['headache'] ?? null,
                'hematemesis'                       => $request->payload['hematemesis'] ?? null,
                'hematuria'                         => $request->payload['hematuria'] ?? null,
                'hemoptysis'                        => $request->payload['hemoptysis'] ?? null,
                'irritability'                      => $request->payload['irritability'] ?? null,
                'jaundice'                          => $request->payload['jaundice'] ?? null,
                'lower_Extremity_Edema'             => $request->payload['lower_Extremity_Edema'] ?? null,
                'myalgia'                           => $request->payload['myalgia'] ?? null,
                'orthopnea'                         => $request->payload['orthopnea'] ?? null,
                'pain'                              => $request->payload['pain'] ?? null,
                'pain_Description'                  => $request->payload['pain_Description'] ?? null,
                'palpitations'                      => $request->payload['palpitations'] ?? null,
                'seizures'                          => $request->payload['seizures'] ?? null,
                'skin_rashes'                       => $request->payload['skin_rashes'] ?? null,
                'stool_BloodyBlackTarry_Mucoid'     => $request->payload['stool_BloodyBlackTarry_Mucoid'] ?? null,
                'sweating'                          => $request->payload['sweating'] ?? null,
                'urgency'                           => $request->payload['urgency'] ?? null,
                'vomitting'                         => $request->payload['vomitting'] ?? null,
                'weightloss'                        => $request->payload['weightloss'] ?? null,
                'others'                            => $request->payload['others'] ?? null,
                'others_Description'                => $request->payload['others_Description'] ?? null,
                'createdby'                         => isset($checkUser->idnumber) 
                                                    ?  $checkUser->idnumber
                                                    :  Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ];
        }

        public function preparePatientPhysicalExamptionChestLungsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'essentially_Normal'                    => $request->payload['essentially_Normal'] ?? null,
                'lumps_Over_Breasts'                    => $request->payload['lumps_Over_Breasts'] ?? null,
                'asymmetrical_Chest_Expansion'          => $request->payload['asymmetrical_Chest_Expansion'] ?? null,
                'rales_Crackles_Rhonchi'                => $request->payload['rales_Crackles_Rhonchi'] ?? null,
                'decreased_Breath_Sounds'               => $request->payload['decreased_Breath_Sounds'] ?? null,
                'intercostalrib_Clavicular_Retraction'  => $request->payload['intercostalrib_Clavicular_Retraction'] ?? null,
                'wheezes'                               => $request->payload['wheezes'] ?? null,
                'others_Description'                    => $request->payload['others_Description'] ?? null,
                'createdby'                             => isset($checkUser->idnumber) 
                                                        ?  $checkUser->idnumber
                                                        :  Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ];
        }

        public function preparePatientCourseInTheWardData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                  => $request->payload['doctors_OrdersAction'] ?? null,
                'createdby'                             => isset($checkUser->idnumber) 
                                                        ?  $checkUser->idnumber
                                                        :  Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ];
        }

        public function preparePatientPhysicalExamptionCVSData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? null,
                'irregular_Rhythm'          => $request->payload['irregular_Rhythm'] ?? null,
                'displaced_Apex_Beat'       => $request->payload['displaced_Apex_Beat'] ?? null,
                'muffled_Heart_Sounds'      => $request->payload['muffled_Heart_Sounds'] ?? null,
                'heaves_AndOR_Thrills'      => $request->payload['heaves_AndOR_Thrills'] ?? null,
                'murmurs'                   => $request->payload['murmurs'] ?? null,
                'pericardial_Bulge'         => $request->payload['pericardial_Bulge'] ?? null,
                'others_Description'        => $request->payload['others_Description'] ?? null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }

        public function preparePatientPhysicalExamptionGeneralSurveyData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? null,
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];
        }

        public function patientPhysicalExamptionHEENTData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? null,
                'icteric_Sclerae'               => $request->payload['icteric_Sclerae'] ?? null,
                'abnormal_Pupillary_Reaction'   => $request->payload['abnormal_Pupillary_Reaction'] ?? null,
                'pale_Conjunctive'              => $request->payload['pale_Conjunctive'] ?? null,
                'cervical_Lympadenopathy'       => $request->payload['cervical_Lympadenopathy'] ?? null,
                'sunken_Eyeballs'               => $request->payload['sunken_Eyeballs'] ?? null,
                'dry_Mucous_Membrane'           => $request->payload['dry_Mucous_Membrane'] ?? null,
                'sunken_Fontanelle'             => $request->payload['sunken_Fontanelle'] ?? null,
                'others_description'            => $request->payload['others_description'] ?? null,
                'createdby'                     => isset($checkUser->idnumber) 
                                                ?  $checkUser->idnumber
                                                :  Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];
        }

        public function patientPhysicalGUIData($request, $checkUser, $patient_id, $registry_id, $existingData = null ) {
            return [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'essentially_Normal'                => $request->payload['essentially_Normal'] ?? null,
                'blood_StainedIn_Exam_Finger'       => $request->payload['blood_StainedIn_Exam_Finger'] ?? null,
                'cervical_Dilatation'               => $request->payload['cervical_Dilatation'] ?? null,
                'presence_Of_AbnormalDischarge'     => $request->payload['presence_Of_AbnormalDischarge'] ?? null,
                'others_Description'                => $request->payload['others_Description'] ?? null,
                'createdby'                         => isset($checkUser->idnumber) 
                                                    ?  $checkUser->idnumber
                                                    :  Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ];
        }

        public function patientPhysicalNeuroExamData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? null,
                'abnormal_Reflexes'             => $request->payload['abnormal_Reflexes'] ?? null,
                'abormal_Gait'                  => $request->payload['abormal_Gait'] ?? null,
                'poor_Altered_Memory'           => $request->payload['poor_Altered_Memory'] ?? null,
                'abnormal_Position_Sense'       => $request->payload['abnormal_Position_Sense'] ?? null,
                'poor_Muscle_Tone_Strength'     => $request->payload['poor_Muscle_Tone_Strength'] ?? null,
                'abnormal_Decreased_Sensation'  => $request->payload['abnormal_Decreased_Sensation'] ?? null,
                'poor_Coordination'             => $request->payload['poor_Coordination'] ?? null,
                'createdby'                     => isset($checkUser->idnumber) 
                                                ?  $checkUser->idnumber
                                                :  Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];
        }

        public function patientPhysicalSkinExtremitiesData($request, $checkUser, $patient_id, $registry_id, $existingData = null ) {
            return [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? null,
                'edema_Swelling'            => $request->payload['edema_Swelling'] ?? null,
                'rashes_Petechiae'          => $request->payload['rashes_Petechiae'] ?? null,
                'clubbing'                  => $request->payload['clubbing'] ?? null,
                'decreased_Mobility'        => $request->payload['decreased_Mobility'] ?? null,
                'weak_Pulses'               => $request->payload['weak_Pulses'] ?? null,
                'cold_Clammy_Skin'          => $request->payload['cold_Clammy_Skin'] ?? null,
                'pale_Nailbeds'             => $request->payload['pale_Nailbeds'] ?? null,
                'cyanosis_Mottled_Skin'     => $request->payload['cyanosis_Mottled_Skin'] ?? null,
                'poor_Skin_Turgor'          => $request->payload['poor_Skin_Turgor'] ?? null,
                'others_Description'        => $request->payload['others_Description'] ?? null,
                'createdby'                 => isset($checkUser->idnumber) 
                                            ?  $checkUser->idnumber
                                            :  Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];
        }
        
        public function patientPregnancyHistoryData($request, $checkUser, $id, $registry_id, $existingData = null) {
            return [
                'OBGYNHistoryID'    => $id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => $request->payload['outcome'] ?? null,
                'deliveryDate'      => $request->payload['deliveryDate'] ?? null,
                'complications'     => $request->payload['complications'] ?? null,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];
        }

        public function patientGynecologicalConditions($request, $checkUser, $id, $registry_id, $existingData = null ) {
            return [
                'OBGYNHistoryID'    => $id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? null,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];
        }

        public function patientMedicationsData($request, $checkUser, $patient_id, $registry_id, $existingData = null ) {
            return [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => $request->payload['item_Id'] ?? null,
                'drug_Description'      => $request->payload['drug_Description'] ?? null,
                'dosage'                => $request->payload['dosage'] ?? null,
                'reason_For_Use'        => $request->payload['reason_For_Use'] ?? null,
                'adverse_Side_Effect'   => $request->payload['adverse_Side_Effect'] ?? null,
                'hospital'              => $request->payload['hospital'] ?? null,
                'isPrescribed'          => $request->payload['isPrescribed'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];
        }

        public function patientPrivilegedCardData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'patient_Id'            => $patient_id,
                'card_number'           => $request->payload['card_number'] ?? '374245455400126',
                'card_Type_Id'          => $request->payload['card_Type_Id'] ?? null,
                'card_BenefitLevel'     => $request->payload['card_BenefitLevel'] ?? null,
                'card_PIN'              => $request->payload['card_PIN'] ?? null,
                'card_Bardcode'         => $request->payload['card_Bardcode'] ?? null,
                'card_RFID'             => $request->payload['card_RFID'] ?? null,
                'card_Balance'          => $request->payload['card_Balance'] ?? null,
                'issued_Date'           => $request->payload['issued_Date'] ?? null,
                'expiry_Date'           => $request->payload['expiry_Date'] ?? null,
                'points_Earned'         => $request->payload['points_Earned'] ?? null,
                'points_Transferred'    => $request->payload['points_Transferred'] ?? null,
                'points_Redeemed'       => $request->payload['points_Redeemed'] ?? null,
                'points_Forfeited'      => $request->payload['points_Forfeited'] ?? null,
                'card_Status'           => $request->payload['card_Status'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];
        }

        public function patientPrivilegedPointTransferData($request, $checkUser, $id, $existingData = null) {
            return [
                'fromCard_Id'       => $id,
                'toCard_Id'         => $id,
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];
        }

        public function patientPrivilegedPointTransactionsData($request, $checkUser, $id, $existingData = null) {
            return [
                'card_Id'           => $id,
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];
        }

        public function patientDischargeInstructionsData($request, $checkUser, $patient_id, $registry_id, $existingData = null) {
            return [
                'branch_Id'                         => $request->payload['branch_Id'] ?? 1,
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'general_Instructions'              => $request->payload['general_Intructions'] ?? null,
                'dietary_Instructions'              => $request->payload['dietary_Intructions'] ?? null,
                'medications_Instructions'          => $request->payload['medications_Intructions'] ?? null,
                'activity_Restriction'              => $request->payload['activity_Restriction'] ?? null,
                'dietary_Restriction'               => $request->payload['dietary_Restriction'] ?? null,
                'addtional_Notes'                   => $request->payload['addtional_Notes'] ?? null,
                'clinicalPharmacist_OnDuty'         => $request->payload['clinicalPharmacist_OnDuty'] ?? null,
                'clinicalPharmacist_CheckTime'      => $request->payload['clinicalPharmacist_CheckTime'] ?? null,
                'nurse_OnDuty'                      => $request->payload['nurse_OnDuty'] ?? null,
                'intructedBy_clinicalPharmacist'    => $request->payload['intructedBy_clinicalPharmacist'] ?? null,
                'intructedBy_Dietitians'            => $request->payload['intructedBy_Dietitians'] ?? null,
                'intructedBy_Nurse'                 => $request->payload['intructedBy_Nurse'] ?? null,
                'createdby'                         => isset($checkUser->idnumber) 
                                                    ?  $checkUser->idnumber
                                                    :  Auth()->user()->idnumber,
                'created_at'                        => Carbon::now()
            ];
        }

        public function patientDischargedMedicationsData($request, $checkUser, $id, $existingData = null) {
            return [
                'instruction_Id'        => $id,
                'Item_Id'               => $request->payload['Item_Id'] ?? null,
                'medication_Name'       => $request->payload['medication_Name'] ?? null,
                'medication_Type'       => $request->payload['medication_Type'] ?? null,
                'dosage'                => $request->payload['dosage'] ?? null,
                'frequency'             => $request->payload['frequency'] ?? null,
                'purpose'               => $request->payload['purpose'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];
        }

        public function patientDischargedFollowUpTreatmentData($request, $checkUser, $id, $existingData = null) {
            return [
                'instruction_Id'        => $id,
                'treatment_Description' => $request->payload['treatment_Description'] ?? null,
                'treatment_Date'        => $request->payload['treatment_Date'] ?? null,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'notes'                 => $request->payload['notes'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];
        }

        public function patientDischargedFollowUpLaboratoriesData($request, $checkUser, $id, $existingData = null) {
            return [
                'instruction_Id'    => $id,
                'item_Id'           => $request->payload['item_Id'] ?? null,
                'test_Name'         => $request->payload['test_Name'] ?? null,
                'test_DateTime'     => $request->payload['test_DateTime'] ?? null,
                'notes'             => $request->payload['notes'] ?? null,
                'createdby'         => isset($checkUser->idnumber) 
                                    ?  $checkUser->idnumber
                                    :  Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];
        }

        public function patientDischargedDoctorsFolloUpData($request, $checkUser, $id, $existingData = null) {
            return [
                'instruction_Id'        => $id,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? null,
                'schedule_Date'         => $request->payload['schedule_Date'] ?? null,
                'createdby'             => isset($checkUser->idnumber) 
                                        ?  $checkUser->idnumber
                                        :  Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];
        }

        private function preparepatientIdentifierData($request) {

            $patientIdentifier = isset($request->payload['patientIdentifier']) ? $request->payload['patientIdentifier'] : null;

            if($patientIdentifier !== null) {
                return [
                    'isHemodialysis'         => ($patientIdentifier === "Hemo Patient") ? true : false,
                    'isPeritoneal'           => ($patientIdentifier === "Peritoneal Patient") ? true : false,
                    'isLINAC'                => ($patientIdentifier === "LINAC") ? true : false,
                    'isCOBALT'               => ($patientIdentifier === "COBALT") ? true : false,
                    'isBloodTrans'           => ($patientIdentifier === "Blood Trans Patient") ? true : false,
                    'isChemotherapy'         => ($patientIdentifier === "Chemo Patient") ? true : false,
                    'isBrachytherapy'        => ($patientIdentifier === "Brachytherapy Patient") ? true : false,
                    'isDebridement'          => ($patientIdentifier === "Debridement") ? true : false,
                    'isTBDots'               => ($patientIdentifier === "TB DOTS") ? true : false,
                    'isPAD'                  => ($patientIdentifier === "PAD Patient") ? true : false,
                    'isRadioTherapy'         => ($patientIdentifier === "Radio Patient") ? true : false,
                ];
            } else {
                return [
                    'isHemodialysis'         => false,
                    'isPeritoneal'           => false,
                    'isLINAC'                => false,
                    'isCOBALT'               => false,
                    'isBloodTrans'           => false,
                    'isChemotherapy'         => false,
                    'isBrachytherapy'        => false,
                    'isDebridement'          => false,
                    'isTBDots'               => false,
                    'isPAD'                  => false,
                    'isRadioTherapy'         => false,
                ];
            }

        }
    }
