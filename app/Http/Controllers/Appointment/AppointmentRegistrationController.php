<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\Appointments\PatientAppointmentsTemporary;
use DB;
use App\Models\Appointments\PatientAppointment;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Helpers\GetIP;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\Appointments\PatientAppointmentTransaction;
use App\Helpers\SMSHelper;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysCashAssessment;
class AppointmentRegistrationController extends Controller
{
    protected function getRegisterPatientData(Request $request, $patient_id = null, $registry_id = null, $patientIdentifier = null, $patient_category = null) {
        return [
            'patientData' => [
                'patient_Id'                => $patient_id,
                'title_id'                  => $request->payload['title_id'] ?? null,
                'lastname'                  => ucwords($request->payload['lastname'] ?? null),
                'firstname'                 => ucwords($request->payload['firstname'] ?? null),
                'middlename'                => ucwords($request->payload['middlename'] ?? null),
                'suffix_id'                 => $request->payload['suffix_id'] ?? null,
                'birthdate'                 => $request->payload['birthdate'] ?? null,
                'sex_id'                    => $request->payload['sex_id'] ?? null,
                'nationality_id'            => $request->payload['nationality_Id'] ?? null,
                'religion_id'               => $request->payload['religion_Id'] ?? null,
                'civilstatus_id'            => $request->payload['civil_Status_Id'] ?? null,
                'typeofbirth_id'            => $request->payload['typeofbirth_id'] ?? null,
                'birthtime'                 => $request->payload['birthtime'] ?? null,
                'birthplace'                => $request->payload['birthplace'] ?? null,
                'age'                       => $request->payload['age'] ?? null,
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? null,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? null,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? null,
                'bldgstreet'                => $request->payload['bldgstreet'] ?? null,
                'region_id'                 => $request->payload['region_Id'] ?? null,
                'province_id'               => $request->payload['province_Id'] ?? null,
                'municipality_id'           => $request->payload['municipality_Id'] ?? null,
                'barangay_id'               => $request->payload['barangay_id'] ?? null,
                'zipcode_id'                => $request->payload['zipcode_Id'] ?? null,
                'country_id'                => $request->payload['country_id'] ?? null,
                'occupation'                => $request->payload['occupation'] ?? null,
                'telephone_number'          => $request->payload['telephone_number'] ?? null,
                'mobile_number'             => $request->payload['mobile_Number'] ?? null,
                'email_address'             => $request->payload['email_Address'] ?? null,
                'isSeniorCitizen'           => $request->payload['isSeniorCitizen'] ?? false,
                'SeniorCitizen_ID_Number'   => $request->payload['SeniorCitizen_ID_Number'] ?? null,
                'isPWD'                     => $request->payload['isPWD'] ?? false,
                'PWD_ID_Number'             => $request->payload['PWD_ID_Number'] ?? null,
                'isPhilhealth_Member'       => $request->payload['isPhilhealth_Member'] ?? false, 
                'Philhealth_Number'         => $request->payload['Philhealth_Number'] ?? null,
                'isEmployee'                => $request->payload['isEmployee'] ?? false,
                'GSIS_Number'               => $request->payload['GSIS_Number'] ?? null,
                'SSS_Number'                => $request->payload['SSS_Number'] ?? null,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? false,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? null,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? null,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? false,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? null,
                'isAbscond'                 => $request->payload['isAbscond'] ?? false,
                'abscond_details'           => $request->payload['abscond_details'] ?? null,
                'dialect_spoken'            => $request->payload['dialect'] ?? null,
                'motherLastname'            => $request->payload['motherLastname'] ?? null,
                'motherFirstname'           => $request->payload['motherFirstname'] ?? null,
                'motherMiddlename'          => $request->payload['motherMiddlename'] ?? null,
                'motherSuffix_id'           => $request->payload['motherSuffix_id'] ?? null,
                'mothertelephone_number'    => $request->payload['mothertelephone_number'] ?? null,
                'mothermobile_number'       => $request->payload['mothermobile_number'] ?? null,
                'motherAddress'             => $request->payload['motherAddress'] ?? null,
                'fatherLastname'            => $request->payload['fatherLastname'] ?? null,
                'fatherFirstname'           => $request->payload['fatherFirstname'] ?? null,
                'fatherMiddlename'          => $request->payload['fatherMiddlename'] ?? null,
                'fatherSuffix_id'           => $request->payload['fatherSuffix_id'] ?? null,
                'fathertelephone_number'    => $request->payload['fathertelephone_number'] ?? null,
                'fathermobile_number'       => $request->payload['fathermobile_number'] ?? null,
                'fatherAddress'             => $request->payload['fatherAddress'] ?? null,
                'spLastname'                => $request->payload['spLastname'] ?? null,
                'spFirstname'               => $request->payload['spFirstname'] ?? null,
                'spMiddlename'              => $request->payload['spMiddlename'] ?? null,
                'spSuffix_id'               => $request->payload['spSuffix_id'] ?? null,
                'sptelephone_number'        => $request->payload['sptelephone_number'] ?? null,
                'spmobile_number'           => $request->payload['spmobile_number'] ?? null,
                'spAddress'                 => $request->payload['spAddress'] ?? null,
                'createdBy'                 => Auth()->guard('patient')->user()->portal_UID,
                'updatedBy'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ],
            'patientRegistryData' => [
                'branch_Id'                     =>  1,
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'register_Source'               => $request->payload['register_Source'] ?? null,
                'register_Casetype'             => $request->payload['register_Casetype'] ?? null,
                'patient_Age'                   => $request->payload['age'] ?? null,
                'mscAccount_type'               => $request->payload['mscAccount_type'] ?? '',
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? null,
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_Trans_Types'] ?? 2, 
                'mscPatient_Category'           => $patient_category,
                'mscPrice_Groups'               => 1,
                'mscPrice_Schemes'              => 1,
                'mscService_Type'               => $request->payload['mscService_Type'] ?? null,
                'queue_number'                  => $request->payload['queue_number'] ?? null,
                'arrived_date'                  => $request->payload['arrived_date'] ?? null,
                'registry_Userid'               => Auth()->guard('patient')->user()->portal_UID,
                'registry_Date'                 => Carbon::now(),
                'registry_Status'               => $request->payload['registry_Status'] ?? null,
                'discharged_Userid'             => $request->payload['discharged_Userid'] ?? null,
                'discharged_Date'               => $request->payload['discharged_Date'] ?? null,
                'billed_Userid'                 => $request->payload['billed_Userid'] ?? null,
                'billed_Date'                   => $request->payload['billed_Date'] ?? null,
                'mscBroughtBy_Relationship_Id'  => $request->payload['mscBroughtBy_Relationship_Id'] ?? null,
                'mscCase_Indicators_Id'         => $request->payload['mscCase_Indicators_Id'] ?? null,
                'billed_Remarks'                => $request->payload['billed_Remarks'] ?? null,
                'mgh_Userid'                    => $request->payload['mgh_Userid'] ?? null,
                'mgh_Datetime'                  => $request->payload['mgh_Datetime'] ?? null,
                'untag_Mgh_Userid'              => $request->payload['untag_Mgh_Userid'] ?? null,
                'untag_Mgh_Datetime'            => $request->payload['untag_Mgh_Datetime'] ?? null,
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? false,
                'hold_Userid'                   => $request->payload['hold_Userid'] ?? null,
                'hold_No'                       => $request->payload['hold_No'] ?? null,
                'hold_Date'                     => $request->payload['hold_Date'] ?? null,
                'hold_Remarks'                  => $request->payload['hold_Remarks'] ?? null,
                'isRevoked'                     => $request->payload['isRevoked'] ?? false,
                'revokedBy'                     => $request->payload['revokedBy'] ?? null,
                'revoked_Date'                  => $request->payload['revoked_Date'] ?? null,
                'revoked_Remarks'               => $request->payload['revoked_Remarks'] ?? null,
                'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? ($patient_id ?? null),
                'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? null,
                'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? null,
                'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? null,
                'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? null,
                'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? null,
                'guarantor_Approval_remarks'    => $request->payload['guarantor_Approval_remarks'] ?? null,
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
                'guarantor_Credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? null,
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? false,
                'philhealth_Number'             => $request->payload['philhealth_Number'] ?? null,
                'isWithMedicalPackage'          => !empty($request->payload['medical_Package_Id']) ? true : ($request->payload['isWithMedicalPackage'] ?? false),
                'medical_Package_Id'            => $request->payload['medical_Package_Id'] ?? null,
                'medical_Package_Name'          => $request->payload['medical_Package_Name'] ?? null,
                'medical_Package_Amount'        => $request->payload['medical_Package_Amount'] ?? null,
                'impression'                    => $request->payload['impression'] ?? null,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? false,
                'illness_Type'                  => $request->payload['illness_Type'] ?? null,
                'isHemodialysis'                => $patientIdentifier === 1 ? true : false,
                'isPeritoneal'                  => $patientIdentifier === 2 ? true : false,
                'isLINAC'                       => $patientIdentifier === 3 ? true : false,
                'isCOBALT'                      => $patientIdentifier === 4 ? true : false,
                'isBloodTrans'                  => $patientIdentifier === 5 ? true : false,
                'isChemotherapy'                => $patientIdentifier === 6 ? true : false,
                'isBrachytherapy'               => $patientIdentifier === 7 ? true : false,
                'isDebridement'                 => $patientIdentifier === 8 ? true : false,
                'isTBDots'                      => $patientIdentifier === 9 ? true : false,
                'isPAD'                         => $patientIdentifier === 10 ? true : false,
                'isRadioTherapy'                => $patientIdentifier === 11 ? true : false,
                'attending_Doctor'              => $request->payload['selectedConsultant'][0]['attending_Doctor'] ?? null,
                'attending_Doctor_fullname'     => $request->payload['selectedConsultant'][0]['attending_Doctor_fullname'] ?? null,
                'bmi'                           => $request->payload['bmi'] ?? null,
                'weight'                        => isset($request->payload['weight']) ? (float)$request->payload['weight'] : null,
                'height'                        => isset($request->payload['height']) ? (float)$request->payload['height'] : null,
                'heightUnit'                    => $request->payload['height_Unit'] ?? null,
                'weightUnit'                    => $request->payload['weightUnit'] ?? null,
                'bloodPressureSystolic'         => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  null,
                'bloodPressureDiastolic'        => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : null,
                'pulseRate'                     => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : null,
                'respiratoryRate'               => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : null,
                'oxygenSaturation'              => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : null,
                'isOpenLateCharges'             => $request->payload['LateCharges'] ?? null,
                'mscCase_result_id'             => $request->payload['mscCase_result_id'] ?? null,
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? false,
                'barcode_Image'                 => $request->payload['barcode_Image'] ?? null,
                'barcode_Code_Id'               => $request->payload['barcode_Code_Id'] ?? null,
                'barcode_Code_String'           => $request->payload['barcode_Code_String'] ?? null,
                'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? false,
                'registry_Remarks'              => $request->payload['registry_Remarks'] ?? null,
                'CreatedBy'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ], 
            'patientPastImmunizationData' => [
                'branch_Id'             => 1,
                'patient_Id'            => $patient_id,
                'vaccine_Id'            => '',
                'administration_Date'   => '',
                'dose'                  => '',
                'site'                  => '',
                'administrator_Name'    => '',
                'notes'                 => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientPastMedicalHistoryData' => [
                'branch_Id'                 => 1,    
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => '',
                'diagnosis_Date'            => '',
                'treament'                  => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(), 
            ],
            'patientPastMedicalProcedureData' => [
                'patient_Id'                => $patient_id,
                'description'               => '',
                'date_Of_Procedure'         => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientPastAllergyHistoryData' => [
                'patient_Id'                => $patient_id,
                'family_History'            => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientPastCauseOfAllergyData' => [
                'history_Id'            => '',
                'allergy_Type_Id'       => '',
                'duration'              => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientPastSymptomsOfAllergyData' => [
                'history_Id'            => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientDrugUsedForAllergyData' => [
                'patient_Id'        => $patient_id,
                'drug_Description'  => '',
                'hospital'          => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientPastBadHabitsData' => [
                'patient_Id'                    => $patient_id,
                'description'                   => null,
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ],
            'patientPrivilegedCard' => [
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
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now()
            ],
            'patientPrivilegedPointTransfers' => [
                'fromCard_Id'       => '',
                'toCard_Id'         => '',
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now()
            ],
            'patientPrivilegedPointTransactions' => [
                'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now()
            ],
            'patientHistoryData' => [
                'branch_Id'                                 => $request->payload['branch_Id'] ?? 1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'brief_History'                             => null,
                'pastMedical_History'                       => null,
                'family_History'                            => null,
                'personalSocial_History'                    => null,
                'chief_Complaint_Description'               => null,
                'impression'                                => null,
                'admitting_Diagnosis'                       => null,
                'discharge_Diagnosis'                       => null,
                'preOperative_Diagnosis'                    => null,
                'postOperative_Diagnosis'                   => null,
                'surgical_Procedure'                        => null,
                'physicalExamination_Skin'                  => null,
                'physicalExamination_HeadEyesEarsNeck'      => null,
                'physicalExamination_Neck'                  => null,
                'physicalExamination_ChestLungs'            => null,
                'physicalExamination_CardioVascularSystem'  => null,
                'physicalExamination_Abdomen'               => null,
                'physicalExamination_GenitourinaryTract'    => null,
                'physicalExamination_Rectal'                => null,
                'physicalExamination_Musculoskeletal'       => null,
                'physicalExamination_LympNodes'             => null,
                'physicalExamination_Extremities'           => null,
                'physicalExamination_Neurological'          => null,
                'createdby'                                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                                => now(),
            ],
            'patientAdministeredMedicineData' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => null,
                'quantity'              => null,
                'administered_Date'     => null,
                'administered_By'       => null,
                'reference_num'         => null,
                'transaction_num'       => null,
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'updatedby'             => Auth()->guard('patient')->user()->portal_UID,
            ],
            'patientAllergyData' => [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'family_History'    => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientCauseAllergyData' => [
                'allergies_Id'      => '',
                'allergy_Type_Id'   => '',
                'duration'          => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientSymptomsOfAllergy' => [
                'allergies_Id'          => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientImmunizationsData' => [
                'branch_id'             => 1,
                'patient_id'            => $patient_id,
                'case_No'               => $registry_id,
                'vaccine_Id'            => '',
                'administration_Date'   => null,
                'dose'                  => null,
                'site'                  => null,
                'administrator_Name'    => null,
                'Notes'                 => null,
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'updatedby'             => Auth()->guard('patient')->user()->portal_UID,
            ],
            'patientMedicalProcedureData' => [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => null,
                'date_Of_Procedure'             => null,
                'performing_Doctor_Id'          => null,
                'performing_Doctor_Fullname'    => null,
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'updatedby'                     => Auth()->guard('patient')->user()->portal_UID,
            ],
            'patientVitalSignsData' => [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'transDate'                 => Carbon::now(),
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  null,
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : null,
                'temperature'               => isset($request->payload['temperatue']) ? (int)$request->payload['temperatue'] : null,
                'pulseRate'                 => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : null,
                'respiratoryRate'           => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : null,
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : null,
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ], 
            'patientBadHabitsData' => [
                'patient_Id' => $patient_id,
                'case_No'   => $registry_id,
                'description' => '',
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ],
            'patientDoctorsData' => [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => '',
                'doctors_Fullname'  => '',
                'role_Id'           => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientPhysicalAbdomenData' => [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => '',
                'palpable_Masses'           => '',
                'abdominal_Rigidity'        => '',
                'uterine_Contraction'       => '',
                'hyperactive_Bowel_Sounds'  => '',
                'others_Description'        => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientPertinentSignAndSymptomsData' => [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'altered_Mental_Sensorium'          => '',
                'abdominal_CrampPain'               => '',
                'anorexia'                          => '',
                'bleeding_Gums'                     => '',
                'body_Weakness'                     => '',
                'blurring_Of_Vision'                => '',
                'chest_PainDiscomfort'              => '',
                'constipation'                      => '',
                'cough'                             => '',
                'diarrhea'                          => '',
                'dizziness'                         => '',
                'dysphagia'                         => '',
                'dysuria'                           => '',
                'epistaxis'                         => '',
                'fever'                             => '',
                'frequency_Of_Urination'            => '',
                'headache'                          => '',
                'hematemesis'                       => '',
                'hematuria'                         => '',
                'hemoptysis'                        => '',
                'irritability'                      => '',
                'jaundice'                          => '',
                'lower_Extremity_Edema'             => '',
                'myalgia'                           => '',
                'orthopnea'                         => '',
                'pain'                              => '',
                'pain_Description'                  => '',
                'palpitations'                      => '',
                'seizures'                          => '',
                'skin_rashes'                       => '',
                'stool_BloodyBlackTarry_Mucoid'     => '',
                'sweating'                          => '',
                'urgency'                           => '',
                'vomitting'                         => '',
                'weightloss'                        => '',
                'others'                            => '',
                'others_Description'                => '',
                'createdby'                         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                        => Carbon::now(),
            ],
            'patientPhysicalExamtionChestLungsData' => [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'essentially_Normal'                    => '',
                'lumps_Over_Breasts'                    => '',
                'asymmetrical_Chest_Expansion'          => '',
                'rales_Crackles_Rhonchi'                => '',
                'decreased_Breath_Sounds'               => '',
                'intercostalrib_Clavicular_Retraction'  => '',
                'wheezes'                               => '',
                'others_Description'                    => '',
                'createdby'                             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                            => Carbon::now(),
            ],
            'patientCourseInTheWardData' => [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                   => '',
                'createdby'                             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                            => Carbon::now(),
            ],
            'patientPhysicalExamtionCVSData' => [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => '',
                'irregular_Rhythm'          => '',
                'displaced_Apex_Beat'       => '',
                'muffled_Heart_Sounds'      => '',
                'heaves_AndOR_Thrills'      => '',
                'murmurs'                   => '',
                'pericardial_Bulge'         => '',
                'others_Description'        => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientPhysicalExamtionGeneralSurveyData' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => '',
                'altered_Sensorium'     => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientPhysicalExamtionHEENTData' => [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => '',
                'icteric_Sclerae'               => '',
                'abnormal_Pupillary_Reaction'   => '',
                'pale_Conjunctive'              => '',
                'cervical_Lympadenopathy'       => '',
                'sunken_Eyeballs'               => '',
                'dry_Mucous_Membrane'           => '',
                'sunken_Fontanelle'             => '',
                'others_description'            => '',
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ],
            'patientPhysicalGUIEData' => [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'essentially_Normal'                => '',
                'blood_StainedIn_Exam_Finger'       => '',
                'cervical_Dilatation'               => '',
                'presence_Of_AbnormalDischarge'     => '',
                'others_Description'                => '',
                'createdby'                         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                        => Carbon::now(),
            ],
            'patientPhysicalNeuroExamData' => [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => '',
                'abnormal_Reflexes'             => '',
                'abormal_Gait'                  => '',
                'poor_Altered_Memory'           => '',
                'abnormal_Position_Sense'       => '',
                'poor_Muscle_Tone_Strength'     => '',
                'abnormal_Decreased_Sensation'  => '',
                'poor_Coordination'             => '',
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ],
            'patientPhysicalSkinExtremitiesData' => [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => '',
                'edema_Swelling'            => '',
                'rashes_Petechiae'          => '',
                'clubbing'                  => '',
                'decreased_Mobility'        => '',
                'weak_Pulses'               => '',
                'cold_Clammy_Skin'          => '',
                'pale_Nailbeds'             => '',
                'cyanosis_Mottled_Skin'     => '',
                'poor_Skin_Turgor'          => '',
                'others_Description'        => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientOBGYNHistory' => [
                'patient_Id'                                            => $patient_id,
                'case_No'                                               => $registry_id,
                'obsteric_Code'                                         => null,
                'menarchAge'                                            => null,
                'menopauseAge'                                          => null,
                'cycleLength'                                           => null,
                'cycleRegularity'                                       => null,
                'lastMenstrualPeriod'                                   => null,
                'contraceptiveUse'                                      => null,
                'lastPapSmearDate'                                      => null,
                'isVitalSigns_Normal'                                   => null,
                'isAscertainPresent_PregnancyisLowRisk'                 => null,
                'riskfactor_MultiplePregnancy'                          => null,
                'riskfactor_OvarianCyst'                                => null,
                'riskfactor_MyomaUteri'                                 => null,
                'riskfactor_PlacentaPrevia'                             => null,
                'riskfactor_Historyof3Miscarriages'                     => null,
                'riskfactor_HistoryofStillbirth'                        => null,
                'riskfactor_HistoryofEclampsia'                         => null,
                'riskfactor_PrematureContraction'                       => null,
                'riskfactor_NotApplicableNone'                          => null,
                'medicalSurgical_Hypertension'                          => null,
                'medicalSurgical_HeartDisease'                          => null,
                'medicalSurgical_Diabetes'                              => null,
                'medicalSurgical_ThyroidDisorder'                       => null,
                'medicalSurgical_Obesity'                               => null,
                'medicalSurgical_ModerateToSevereAsthma'                => null,
                'medicalSurigcal_Epilepsy'                              => null,
                'medicalSurgical_RenalDisease'                          => null,
                'medicalSurgical_BleedingDisorder'                      => null,
                'medicalSurgical_HistoryOfPreviousCesarianSection'      => null,
                'medicalSurgical_HistoryOfUterineMyomectomy'            => null,
                'medicalSurgical_NotApplicableNone'                     => null,
                'deliveryPlan_OrientationToMCP'                         => null,
                'deliveryPlan_ExpectedDeliveryDate'                     => null,
                'followUp_Prenatal_ConsultationNo_2nd'                  => null,
                'followUp_Prenatal_DateVisit_2nd'                       => null,
                'followUp_Prenatal_AOGInWeeks_2nd'                      => null,
                'followUp_Prenatal_Weight_2nd'                          => null,
                'followUp_Prenatal_CardiacRate_2nd'                     => null,
                'followUp_Prenatal_RespiratoryRate_2nd'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_2nd'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_2nd'           => null,
                'followUp_Prenatal_Temperature_2nd'                     => null,
                'followUp_Prenatal_ConsultationNo_3rd'                  => null,
                'followUp_Prenatal_DateVisit_3rd'                       => null,
                'followUp_Prenatal_AOGInWeeks_3rd'                      => null,
                'followUp_Prenatal_Weight_3rd'                          => null,
                'followUp_Prenatal_CardiacRate_3rd'                     => null,
                'followUp_Prenatal_RespiratoryRate_3rd'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_3rd'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_3rd'           => null,
                'followUp_Prenatal_Temperature_3rd'                     => null,
                'followUp_Prenatal_ConsultationNo_4th'                  => null,
                'followUp_Prenatal_DateVisit_4th'                       => null,
                'followUp_Prenatal_AOGInWeeks_4th'                      => null,
                'followUp_Prenatal_Weight_4th'                          => null,
                'followUp_Prenatal_CardiacRate_4th'                     => null,
                'followUp_Prenatal_RespiratoryRate_4th'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_4th'            => null,
                'followUp_Prenatal_ConsultationNo_5th'                  => null,
                'followUp_Prenatal_DateVisit_5th'                       => null,
                'followUp_Prenatal_AOGInWeeks_5th'                      => null,
                'followUp_Prenatal_Weight_5th'                          => null,
                'followUp_Prenatal_CardiacRate_5th'                     => null,
                'followUp_Prenatal_RespiratoryRate_5th'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_5th'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_5th'           => null,
                'followUp_Prenatal_Temperature_5th'                     => null,
                'followUp_Prenatal_ConsultationNo_6th'                  => null,
                'followUp_Prenatal_DateVisit_6th'                       => null,
                'followUp_Prenatal_AOGInWeeks_6th'                      => null,
                'followUp_Prenatal_Weight_6th'                          => null,
                'followUp_Prenatal_CardiacRate_6th'                     => null,
                'followUp_Prenatal_RespiratoryRate_6th'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_6th'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_6th'           => null,
                'followUp_Prenatal_Temperature_6th'                     => null,
                'followUp_Prenatal_ConsultationNo_7th'                  => null,
                'followUp_Prenatal_DateVisit_7th'                       => null,
                'followUp_Prenatal_AOGInWeeks_7th'                      => null,
                'followUp_Prenatal_Weight_7th'                          => null,
                'followUp_Prenatal_CardiacRate_7th'                     => null,
                'followUp_Prenatal_RespiratoryRate_7th'                 => null,
                'followUp_Prenatal_BloodPresureDiastolic_7th'           => null,
                'followUp_Prenatal_Temperature_7th'                     => null,
                'followUp_Prenatal_ConsultationNo_8th'                  => null,
                'followUp_Prenatal_DateVisit_8th'                       => null,
                'followUp_Prenatal_AOGInWeeks_8th'                      => null,
                'followUp_Prenatal_Weight_8th'                          => null,
                'followUp_Prenatal_CardiacRate_8th'                     => null,
                'followUp_Prenatal_RespiratoryRate_8th'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_8th'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_8th'           => null,
                'followUp_Prenatal_Temperature_8th'                     => null,
                'followUp_Prenatal_ConsultationNo_9th'                  => null,
                'followUp_Prenatal_DateVisit_9th'                       => null,
                'followUp_Prenatal_AOGInWeeks_9th'                      => null,
                'followUp_Prenatal_Weight_9th'                          => null,
                'followUp_Prenatal_CardiacRate_9th'                     => null,
                'followUp_Prenatal_RespiratoryRate_9th'                 => null,
                'followUp_Prenatal_BloodPresureSystolic_9th'            => null,
                'followUp_Prenatal_BloodPresureDiastolic_9th'           => null,
                'followUp_Prenatal_Temperature_9th'                     => null,
                'followUp_Prenatal_ConsultationNo_10th'                 => null,
                'followUp_Prenatal_DateVisit_10th'                      => null,
                'followUp_Prenatal_AOGInWeeks_10th'                     => null,
                'followUp_Prenatal_Weight_10th'                         => null,
                'followUp_Prenatal_CardiacRate_10th'                    => null,
                'followUp_Prenatal_RespiratoryRate_10th'                => null,
                'followUp_Prenatal_BloodPresureSystolic_10th'           => null,
                'followUp_Prenatal_BloodPresureDiastolic_10th'          => null,
                'followUp_Prenatal_Temperature_10th'                    => null,
                'followUp_Prenatal_ConsultationNo_11th'                 => null,
                'followUp_Prenatal_DateVisit_11th'                      => null,
                'followUp_Prenatal_AOGInWeeks_11th'                     => null,
                'followUp_Prenatal_Weight_11th'                         => null,
                'followUp_Prenatal_CardiacRate_11th'                    => null,
                'followUp_Prenatal_RespiratoryRate_11th'                => null,
                'followUp_Prenatal_BloodPresureSystolic_11th'           => null,
                'followUp_Prenatal_BloodPresureDiastolic_11th'          => null,
                'followUp_Prenatal_Temperature_11th'                    => null,
                'followUp_Prenatal_ConsultationNo_12th'                 => null,
                'followUp_Prenatal_DateVisit_12th'                      => null,
                'followUp_Prenatal_AOGInWeeks_12th'                     => null,
                'followUp_Prenatal_Weight_12th'                         => null,
                'followUp_Prenatal_CardiacRate_12th'                    => null,
                'followUp_Prenatal_RespiratoryRate_12th'                => null,
                'followUp_Prenatal_BloodPresureSystolic_12th'           => null,
                'followUp_Prenatal_BloodPresureDiastolic_12th'          => null,
                'followUp_Prenatal_Temperature_12th'                    => null,
                'followUp_Prenatal_Remarks'                             => null,
                'createdby'                                             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                                            => Carbon::now(),
            ],
            'patientPregnancyHistoryData' => [
                'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => '',
                'deliveryDate'      => '',
                'complications'     => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientGynecologicalConditions' => [
                'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => '',
                'createdby'         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'        => Carbon::now(),
            ],
            'patientMedicationsData' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => '',
                'drug_Description'      => '',
                'dosage'                => '',
                'reason_For_Use'        => '',
                'adverse_Side_Effect'   => '',
                'hospital'              => '',
                'isPrescribed'          => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientDischargeInstructions' => [
                'branch_Id'                         => 1,
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'general_Instructions'              => '',
                'dietary_Instructions'              => '',
                'medications_Instructions'          => '',
                'activity_Restriction'              => '',
                'dietary_Restriction'               => '',
                'addtional_Notes'                  => '',
                'clinicalPharmacist_OnDuty'         => '',
                'clinicalPharmacist_CheckTime'      => '',
                'nurse_OnDuty'                      => '',
                'intructedBy_clinicalPharmacist'    => '',
                'intructedBy_Dietitians'           => '',
                'intructedBy_Nurse'                => '',
                'createdby'                         => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                        => Carbon::now(),
            ],
            'patientDischargeDoctorsFollowUp' => [
                'instruction_Id'            => '',
                'doctor_Id'                 => '',
                'doctor_Name'               => '',
                'doctor_Specialization'     => '',
                'schedule_Date'             => '',
                'createdby'                 => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                => Carbon::now(),
            ],
            'patientDischargeFollowUpTreatment' => [
                'instruction_Id'                => '',
                'treatment_Description'         => '',
                'treatment_Date'                => '',
                'doctor_Id'                     => '',
                'doctor_Name'                   => '',
                'notes'                         => '',
                'createdby'                     => Auth()->guard('patient')->user()->portal_UID,
                'created_at'                    => Carbon::now(),
            ],
            'patientDischargeMedications' => [
                'instruction_Id'        => '',
                'item_Id'               => '',
                'medication_Name'       => '',
                'medication_Type'       => '',
                'dosage'                => '',
                'frequency'             => '',
                'purpose'               => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
            'patientDischargeFollowUpLaboratories' => [
                'instruction_Id'        => '',
                'item_Id'               => '',
                'test_Name'             => '',
                'test_DateTime'         => '',
                'notes'                 => '',
                'createdby'             => Auth()->guard('patient')->user()->portal_UID,
                'created_at'            => Carbon::now(),
            ],
        ];
    }

    public function register(Request $request) {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        
        try {
            $existingMedsysPatient  = MedsysPatientMaster::where('lastname', $request->payload['lastname'])->where('firstname', $request->payload['firstname'])->whereDate('birthdate', $request->payload['birthdate'])->first();
          
            $today                  = $request->schedule ? $request->schedule : Carbon::now();
            SystemSequence::whereIn('code', ['MPID', 'MOPD'])->increment('seq_no');

            $sequence               = SystemSequence::where('code','MPID')->where('branch_id', 1)->first();
            $registry_sequence      = SystemSequence::where('code','MOPD')->where('branch_id', 1)->first();
            $chargeslip_sequence    = SystemSequence::where('code', 'GCN')->first();
            $assessnum_sequence     = SystemSequence::where('code', 'GAN')->first();
            if (!$sequence || !$registry_sequence) {
                throw new \Exception('Sequence not found');
            }

            $registry_id        = $registry_sequence->seq_no;
            $patientIdentifier  = $request->payload['patientIdentifier'] ?? null;

            $existingPatient    = Patient::where('lastname', $request->payload['lastname'])->where('firstname', $request->payload['firstname'])->whereDate('birthdate', $request->payload['birthdate'])->first();
            if ($existingPatient) {
                $patient_id = $existingMedsysPatient ? $existingMedsysPatient->HospNum : $existingPatient->patient_Id;
                $patient_category = 3;
            } else {
                $patient_category = 2;
                $patient_id = $existingMedsysPatient ? $existingMedsysPatient->HospNum : $sequence->seq_no;
                
                $sequence->update([
                    // 'seq_no'           => $sequence->seq_no + 1,
                    'recent_generated' => $sequence->seq_no,
                ]);
            }

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
            ];
            
            $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category);
            $patient = Patient::whereDate('birthdate', $request->payload['birthdate'])->updateOrCreate($patientRule, $registerData['patientData']);
            
            if (!$patient) {
                throw new \Exception('Failed to create patient master data');
            } else {
                $patient->past_medical_procedures()->create($registerData['patientPastMedicalProcedureData']);
                $patient->past_medical_history()->create($registerData['patientPastMedicalHistoryData']);
                $patient->past_immunization()->create($registerData['patientPastImmunizationData']);
                $patient->past_bad_habits()->create($registerData['patientPastBadHabitsData']);
                $patient->drug_used_for_allergy()->create($registerData['patientDrugUsedForAllergyData']);

                $patientPriviledgeCard = $patient->privilegedCard()->create($registerData['patientPrivilegedCard']);
                $registerData['patientPrivilegedPointTransfers']['fromCard_Id'] = $patientPriviledgeCard->id;
                $registerData['patientPrivilegedPointTransfers']['toCard_Id'] = $patientPriviledgeCard->id;
                $registerData['patientPrivilegedPointTransactions']['card_Id'] = $patientPriviledgeCard->id;
                $patientPriviledgeCard->pointTransactions()->create($registerData['patientPrivilegedPointTransactions']);
                $patientPriviledgeCard->pointTransfers()->create($registerData['patientPrivilegedPointTransfers']);
    
                $pastHistory = $patient->past_allergy_history()->create($registerData['patientPastAllergyHistoryData']);
                $registerData['patientPastCauseOfAllergyData']['history_Id'] = $pastHistory->id;
                $registerData['patientPastSymptomsOfAllergyData']['history_Id'] = $pastHistory->id;
                $pastHistory->pastCauseOfAllergy()->create($registerData['patientPastCauseOfAllergyData']);
                $pastHistory->pastSymptomsOfAllergy()->create($registerData['patientPastSymptomsOfAllergyData']);
            }

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingRegistry) {
                $patientRegistry = $patient->patientRegistry()->create($registerData['patientRegistryData']);
                $patientRegistry->history()->create($registerData['patientHistoryData']);
                $patientRegistry->immunizations()->create($registerData['patientImmunizationsData']);
                $patientRegistry->vitals()->create($registerData['patientVitalSignsData']);
                $patientRegistry->medical_procedures()->create($registerData['patientMedicalProcedureData']);
                $patientRegistry->administered_medicines()->create($registerData['patientAdministeredMedicineData']);
                $patientRegistry->bad_habits()->create($registerData['patientBadHabitsData']);
                $patientRegistry->patientDoctors()->create($registerData['patientDoctorsData']);
                $patientRegistry->pertinentSignAndSymptoms()->create($registerData['patientPertinentSignAndSymptomsData']);
                $patientRegistry->physicalExamtionChestLungs()->create($registerData['patientPhysicalExamtionChestLungsData']);
                $patientRegistry->courseInTheWard()->create($registerData['patientCourseInTheWardData']);
                $patientRegistry->physicalExamtionCVS()->create($registerData['patientPhysicalExamtionCVSData']);
                $patientRegistry->medications()->create($registerData['patientMedicationsData']);
                $patientRegistry->physicalExamtionHEENT()->create($registerData['patientPhysicalExamtionHEENTData']);
                $patientRegistry->physicalSkinExtremities()->create($registerData['patientPhysicalSkinExtremitiesData']);
                $patientRegistry->physicalAbdomen()->create($registerData['patientPhysicalAbdomenData']);
                $patientRegistry->physicalNeuroExam()->create($registerData['patientPhysicalNeuroExamData']);
                $patientRegistry->physicalGUIE()->create($registerData['patientPhysicalGUIEData']);
                $patientRegistry->PhysicalExamtionGeneralSurvey()->create($registerData['patientPhysicalExamtionGeneralSurveyData']);

                $OBG = $patientRegistry->oBGYNHistory()->create($registerData['patientOBGYNHistory']);
                $registerData['patientPregnancyHistoryData']['OBGYNHistoryID'] = $OBG->id;
                $registerData['patientGynecologicalConditions']['OBGYNHistoryID'] = $OBG->id;
                $OBG->PatientPregnancyHistory()->create($registerData['patientPregnancyHistoryData']);
                $OBG->gynecologicalConditions()->create($registerData['patientGynecologicalConditions']);

                $patientAllergy = $patientRegistry->allergies()->create($registerData['patientAllergyData']);
                $registerData['patientCauseAllergyData']['allergies_Id'] = $patientAllergy->id;
                $registerData['patientSymptomsOfAllergy']['allergies_Id'] = $patientAllergy->id;
                $patientAllergy->cause_of_allergy()->create($registerData['patientCauseAllergyData']);
                $patientAllergy->symptoms_allergy()->create($registerData['patientSymptomsOfAllergy']);

                $patientDischarge = $patientRegistry->dischargeInstructions()->create($registerData['patientDischargeInstructions']);
                $registerData['patientDischargeMedications']['instruction_Id'] = $patientDischarge->id;
                $registerData['patientDischargeFollowUpLaboratories']['instruction_Id'] = $patientDischarge->id;
                $registerData['patientDischargeFollowUpTreatment']['instruction_Id'] = $patientDischarge->id;
                $registerData['patientDischargeDoctorsFollowUp']['instruction_Id'] = $patientDischarge->id;
                $patientDischarge->dischargeMedications()->create($registerData['patientDischargeMedications']);
                $patientDischarge->dischargeFollowUpLaboratories()->create($registerData['patientDischargeFollowUpLaboratories']);
                $patientDischarge->dischargeFollowUpTreatment()->create($registerData['patientDischargeFollowUpTreatment']);
                $patientDischarge->dischargeDoctorsFollowUp()->create($registerData['patientDischargeDoctorsFollowUp']);

            } else {
                throw new \Exception('Patient already registered today');
            }

            if (!$patient || !$patientRegistry) {
                throw new \Exception('Registration failed, rollback transaction');
            } else {
                $registry_sequence->update([
                    // 'seq_no' => $registry_sequence->seq_no + 1, 
                    'recent_generated' => $registry_sequence->seq_no,
                ]);

                $id = $request->id;
                $temporary_id = $request->payload['id'];
                PatientAppointmentsTemporary::where('id',$temporary_id)->update([
                    'patient_id'=>$patient_id,
                ]);
                $patient_appointment = PatientAppointment::where('id', $id)->first();
                $transaction = PatientAppointmentTransaction::where('appointment_ReferenceNumber',$patient_appointment['appointment_ReferenceNumber'])->get();
                foreach($transaction as $row){
                    $sequence = $row['transaction_Code'] . $chargeslip_sequence->seq_no;
                    CashAssessment::create([
                        'branch_id' => 1,
                        'patient_id' => $patient_id,
                        'case_no' => $registry_id,
                        'patient_name' => $patient_appointment['patient']['name'],
                        'transdate' =>Carbon::now(),
                        'assessnum' => $assessnum_sequence->seq_no,
                        'drcr' => 'C',
                        'revenueID' => $row['transaction_Code'],
                        'itemID' => $row['item_Id'],
                        'quantity' => 1,
                        'refNum' => $sequence,
                        'amount' =>$row['total_Amount'],
                        'requestDoctorID' => $patient_appointment['doctor']['doctor_code'],
                        'requestDoctorName' => $patient_appointment['doctor']['doctor_name'],
                        'departmentID' => $row['transaction_Code'],
                        'userId' => Auth()->guard('patient')->user()->portal_UID,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => Auth()->guard('patient')->user()->portal_UID,
                        'created_at' => Carbon::now(),
                    ]);

                    MedsysCashAssessment::create([
                        'HospNum'   => $patient_id,
                        'IdNum'   => $registry_id.'B',
                        'Name'   => $patient_appointment['patient']['name'],
                        'TransDate' => Carbon::now(),
                        'AssessNum' => $assessnum_sequence->seq_no,
                        'Indicator' => $row['transaction_Code'],
                        'DrCr' => 'C',
                        'ItemID' => $row['item_Id'],
                        'Quantity' => 1,
                        'RefNum' => $sequence,
                        'Amount' => $row['total_Amount'],
                        'UserID' => Auth()->guard('patient')->user()->portal_UID,
                        'RevenueID' => $row['transaction_Code'],
                        'RequestDocID' => $patient_appointment['doctor']['doctor_code'],
                    ]);
                }

                $patient_appointment->update(
                    [
                        'patient_Id'=>$patient_id,
                        'case_No'=>$registry_id,
                        'status_Id'=>2,
                        'updated_at'=>Carbon::now()
                    ]
                );

                $chargeslip_sequence->update([
                    'seq_no' => $chargeslip_sequence->seq_no + 1,
                    'recent_generated' => $chargeslip_sequence->seq_no
                ]);
                $assessnum_sequence->update([
                    'seq_no' => $assessnum_sequence->seq_no + 1,
                    'recent_generated' => $assessnum_sequence->seq_no
                ]);
                DB::connection('sqlsrv')->commit();
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_billingOut')->commit();
                $mobileno       = $request->payload['mobile_Number'];
                $patient_name   = $request->payload['name'];
                $schedule       = $request->schedule;
                $refno          = $request->refno;
                $data = [
                    'patient_name' => $patient_name,
                    'date_schedule' => $schedule,
                    'reference_no' => $refno,
                ];
                $phoneNumberWithoutLeadingZero = ltrim($mobileno, '0');
                $helpersms = new SMSHelper();
                $helpersms->sendSms($phoneNumberWithoutLeadingZero,SMSHelper::confirmed_message($data));
                return response()->json([
                    'message' => 'Outpatient data registered successfully',
                    'patient' => $patient,
                    'patientRegistry' => $patientRegistry
                ], 200);
            }

        } catch(\Exception $e) {
            
            DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
