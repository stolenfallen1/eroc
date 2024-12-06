<?php

namespace App\Http\Controllers\HIS\services;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\MedsysSeriesNo;
use App\Models\HIS\PatientPastImmunizations;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\GetIP;

class OutpatientRegistrationController extends Controller
{
    protected $check_is_allow_medsys;

    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }

    // TODO: Create Helper for this soon but make it functional first
    protected function getRegisterPatientData(Request $request, $patient_id = null, $registry_id = null, $patientIdentifier = null, $patient_category = null, $item = null, $symptom = null) {
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
                'nationality_id'            => $request->payload['nationality_id'] ?? null,
                'religion_id'               => $request->payload['religion_id'] ?? null,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? null,
                'typeofbirth_id'            => $request->payload['typeofbirth_id'] ?? null,
                'birthtime'                 => $request->payload['birthtime'] ?? null,
                'birthplace'                => $request->payload['birthplace'] ?? null,
                'age'                       => $request->payload['age'] ?? null,
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? null,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? null,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? null,
                'bldgstreet'                => $request->payload['address']['bldgstreet'] ?? null,
                'region_id'                 => $request->payload['address']['region_id'] ?? null,
                'province_id'               => $request->payload['address']['province_id'] ?? null,
                'municipality_id'           => $request->payload['address']['municipality_id'] ?? null,
                'barangay_id'               => $request->payload['address']['barangay_id'] ?? null,
                'zipcode_id'                => $request->payload['address']['zipcode_id'] ?? null,
                'country_id'                => $request->payload['address']['country_id'] ?? null,
                'occupation'                => $request->payload['occupation'] ?? null,
                'telephone_number'          => $request->payload['telephone_number'] ?? null,
                'mobile_number'             => $request->payload['mobile_number'] ?? null,
                'email_address'             => $request->payload['email_address'] ?? null,
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
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ],
            'patientRegistryData' => [
                'branch_Id'                     =>  1,
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'er_Case_No'                    => null,
                'register_Source'               => $request->payload['register_Source'] ?? null,
                'register_Casetype'             => $request->payload['register_Casetype'] ?? null,
                'register_Link_Case_No'         => null,
                'register_Case_No_Consolidate'  => null,
                'patient_Age'                   => $request->payload['age'] ?? null,
                'er_Bedno'                      => null,
                'room_Code'                     => null,
                'room_Rate'                     => null,
                'mscAccount_type'               => $request->payload['mscAccount_type'] ?? '',
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? null,
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_Trans_Types'] ?? 2, 
                'mscAdmission_Type_Id'          => null,
                'mscPatient_Category'           => $patient_category,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? null,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? 100,
                'mscService_Type'               => $request->payload['mscService_Type'] ?? null,
                'mscPrivileged_Card_Id'         => $request->payload['mscPrivileged_Card_Id'] ?? null,
                'queue_number'                  => $request->payload['queue_number'] ?? null,
                'arrived_date'                  => $request->payload['arrived_date'] ?? null,
                'registry_Userid'               => Auth()->user()->idnumber,
                'registry_Date'                 => Carbon::now(),
                'registry_Status'               => $request->payload['registry_Status'] ?? null,
                'discharged_Userid'             => null,
                'discharged_Date'               => null,
                'discharged_Hostname'           => null,
                'billed_Userid'                 => null,
                'billed_Date'                   => null,
                'billed_Hostname'               => null,
                'mscBroughtBy_Relationship_Id'  => $request->payload['mscBroughtBy_Relationship_Id'] ?? null,
                'mscCase_Indicators_Id'         => $request->payload['mscCase_Indicators_Id'] ?? null,
                'billed_Remarks'                => $request->payload['billed_Remarks'] ?? null,
                'mgh_Userid'                    => null,
                'mgh_Datetime'                  => null,
                'mgh_Hostname'                  => null,
                'untag_Mgh_Userid'              => null,
                'untag_Mgh_Datetime'            => null,
                'untag_Mgh_Hostname'            => null,
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
                'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? ("PERSONAL" ?? null),
                'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? null,
                'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? null,
                'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? null,
                'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? null,
                'guarantor_Approval_remarks'    => $request->payload['guarantor_Approval_remarks'] ?? null,
                'isWithCreditLimit'             => isset($request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit']) 
                                                    && !empty($request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'])
                                                    ? true 
                                                    : false,
                'guarantor_Credit_Limit'        => isset($request->payload['selectedGuarantor'][0]['isOpen']) 
                                                    && $request->payload['selectedGuarantor'][0]['isOpen'] 
                                                    ? null 
                                                    : ($request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? null),
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
                'CreatedBy'                     => Auth()->user()->idnumber,
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
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ],
            'patientPastMedicalHistoryData' => [
                'branch_Id'                 => 1,    
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => '',
                'diagnosis_Date'            => '',
                'treament'                  => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(), 
            ],
            'patientPastMedicalProcedureData' => [
                'patient_Id'                => $patient_id,
                'description'               => '',
                'date_Of_Procedure'         => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ],
            'patientPastBadHabitsData' => [
                'patient_Id'                    => $patient_id,
                'description'                   => null,
                'createdby'                     => Auth()->user()->idnumber,
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
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ],
            'patientPrivilegedPointTransfers' => [
                'fromCard_Id'       => '',
                'toCard_Id'         => '',
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ],
            'patientPrivilegedPointTransactions' => [
                'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
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
                'createdby'                                 => Auth()->user()->idnumber,
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
                'createdby'             => Auth()->user()->idnumber,
                'updatedby'             => Auth()->user()->idnumber,
            ],
            'patientAllergyData' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'allergy_type_id'       => $item['allergy_id'] ?? null,
                'allergy_description'   => $item['allergy_name'] ?? null,
                'family_History'        => null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ],
            'patientCauseAllergyData' => [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'assessID'          => '',
                'allergy_Type_Id'   => $item['allergy_id'] ?? null,
                'description'       => $item['cause'] ?? null,
                'duration'          => null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ],
            'patientSymptomsOfAllergy' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'assessID'              => '',
                'allergy_Type_Id'       => $item['allergy_id'] ?? null,
                'symptom_id'            => $symptom['id'] ?? null,
                'symptom_Description'   => $symptom['description'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ],
            'patientDrugUsedForAllergyData' => [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'assessID'          => '',
                'allergy_Type_Id'       => $item['allergy_id'] ?? null,
                'drug_Description'  => $item['drugUsed'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
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
                'createdby'             => Auth()->user()->idnumber,
                'updatedby'             => Auth()->user()->idnumber,
            ],
            'patientMedicalProcedureData' => [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => null,
                'date_Of_Procedure'             => null,
                'performing_Doctor_Id'          => null,
                'performing_Doctor_Fullname'    => null,
                'createdby'                     => Auth()->user()->idnumber,
                'updatedby'                     => Auth()->user()->idnumber,
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
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ], 
            'patientBadHabitsData' => [
                'patient_Id' => $patient_id,
                'case_No'   => $registry_id,
                'description' => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ],
            'patientDoctorsData' => [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => '',
                'doctors_Fullname'  => '',
                'role_Id'           => '',
                'createdby'         => Auth()->user()->idnumber,
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
                'createdby'                 => Auth()->user()->idnumber,
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
                'createdby'                         => Auth()->user()->idnumber,
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
                'createdby'                             => Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ],
            'patientCourseInTheWardData' => [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                   => '',
                'createdby'                             => Auth()->user()->idnumber,
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
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ],
            'patientPhysicalExamtionGeneralSurveyData' => [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => '',
                'altered_Sensorium'     => '',
                'createdby'             => Auth()->user()->idnumber,
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
                'createdby'                     => Auth()->user()->idnumber,
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
                'createdby'                         => Auth()->user()->idnumber,
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
                'createdby'                     => Auth()->user()->idnumber,
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
                'createdby'                 => Auth()->user()->idnumber,
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
                'createdby'                                             => Auth()->user()->idnumber,
                'created_at'                                            => Carbon::now(),
            ],
            'patientPregnancyHistoryData' => [
                'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => '',
                'deliveryDate'      => '',
                'complications'     => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ],
            'patientGynecologicalConditions' => [
                'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => '',
                'createdby'         => Auth()->user()->idnumber,
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
                'createdby'             => Auth()->user()->idnumber,
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
                'createdby'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ],
            'patientDischargeDoctorsFollowUp' => [
                'instruction_Id'            => '',
                'doctor_Id'                 => '',
                'doctor_Name'               => '',
                'doctor_Specialization'     => '',
                'schedule_Date'             => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ],
            'patientDischargeFollowUpTreatment' => [
                'instruction_Id'                => '',
                'treatment_Description'         => '',
                'treatment_Date'                => '',
                'doctor_Id'                     => '',
                'doctor_Name'                   => '',
                'notes'                         => '',
                'createdby'                     => Auth()->user()->idnumber,
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
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ],
            'patientDischargeFollowUpLaboratories' => [
                'instruction_Id'        => '',
                'item_Id'               => '',
                'test_Name'             => '',
                'test_DateTime'         => '',
                'notes'                 => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ],
        ];
    }

    // TODO: Create Helper for this soon but make it functional first
    protected function getUniqueAllergy($records) 
    {
        $uniqueRecords = [];
        foreach ($records as $record) {
            if (!in_array($record['assessID'], array_column($uniqueRecords, 'assessID'))) {
                $uniqueRecords[] = $record;
            }
        }
        return $uniqueRecords;
    }
    public function index() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            
            $data->with([
                'sex', 
                'civilStatus', 
                'region', 
                'provinces', 
                'municipality', 
                'barangay', 
                'countries',
                'patientRegistry' => function ($query) use ($today) {
                            $query->whereDate('registry_Date', $today);
                },
                'patientRegistry.allergies' => function ($query) {
                    $query->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy')
                            ->where('isDeleted', '!=', 1);
                }
            ]);

            $data->whereHas('patientRegistry', function ($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 2)
                        ->where('isRevoked', 0)
                        ->whereNull('discharged_Date')
                        ->whereDate('registry_Date', $today);
                if (Request()->keyword) {
                    $query->where(function($subQuery) {
                        $subQuery->where('lastname', 'LIKE', '%'.Request()->keyword.'%')
                                    ->orWhere('firstname', 'LIKE', '%'.Request()->keyword.'%')
                                    ->orWhere('patient_Id', 'LIKE', '%'.Request()->keyword.'%')
                                    ->orWhere('case_No', 'LIKE', '%'.Request()->keyword.'%');
                    });
                }
            });
    
            $data->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get outpatient patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function register(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {
            SystemSequence::where('code', 'MPID')->increment('seq_no');
            SystemSequence::where('code', 'MERN')->increment('seq_no');
            SystemSequence::where('code', 'MOPD')->increment('seq_no');

            SystemSequence::where('code', 'MPID')->increment('recent_generated');
            SystemSequence::where('code', 'MERN')->increment('recent_generated');
            SystemSequence::where('code', 'MOPD')->increment('recent_generated');

            $sequence = SystemSequence::where('code', 'MPID')->select('seq_no')->where('branch_id', 1)->first();
            $registry_sequence = SystemSequence::where('code', 'MOPD')->select('seq_no')->where('branch_id', 1)->first();
            if (!$sequence || !$registry_sequence) {
                throw new \Exception('Sequence not found');
            }

            if($this->check_is_allow_medsys) {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');

                $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'OPDId')->first();

                $patient_id     = $check_medsys_series_no->HospNum;
                $registry_id    = $check_medsys_series_no->OPDId;

            } else {
                $patient_id     = intval($sequence->seq_no);
                $registry_id    = intval($registry_sequence->seq_no);
            }

            $today = Carbon::now();
            $patientIdentifier  = $request->payload['patientIdentifier'] ?? null;
            $existingPatient = Patient::where('lastname', $request->payload['lastname'])->where('firstname', $request->payload['firstname'])->where('birthdate', $request->payload['birthdate'])->first();
            
            if ($existingPatient) {
                $patient_id = $existingPatient->patient_Id;
                $patient_category = 3;
            } else {
                $patient_category = 2;
                $patient_id = $sequence->seq_no;
                $sequence->where('code', 'MPID')->update([
                    'seq_no'            => $patient_id,
                    'recent_generated'  => $patient_id,
                ]);
                $registry_sequence->where('code', 'MOPD')->update([
                    'seq_no'            => $registry_id,
                    'recent_generated'  => $registry_id,
                ]);
                $registry_sequence->where('code', 'MERN')->update([
                    'seq_no'            => $registry_id,
                    'recent_generated'  => $registry_id,
                ]);
            }

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
            ];
            
            $allergyResults = [
                'patientAllergyData' => [],
                'patientCauseAllergyData' => [],
                'patientSymptomsOfAllergy' => [],
                'patientDrugUsedForAllergyData' => [],
            ];
            
            $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category);

            if (isset($request->payload['selectedAllergy']) && is_array($request->payload['selectedAllergy'])) {
                foreach ($request->payload['selectedAllergy'] as $item) {
                    $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category, $item);
                    
                    $allergyResults['patientAllergyData'][] = $registerData['patientAllergyData'];
                    $allergyResults['patientCauseAllergyData'][] = $registerData['patientCauseAllergyData'];
                    $allergyResults['patientDrugUsedForAllergyData'][] = $registerData['patientDrugUsedForAllergyData'];

                    if (isset($item['symptoms']) && is_array($item['symptoms'])) {
                        foreach ($item['symptoms'] as $symptom) {
                                $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category, $item, $symptom);
                                $allergyResults['patientSymptomsOfAllergy'][] = $registerData['patientSymptomsOfAllergy'];
                        }
                    }
                }
            }
            
            $patient = Patient::updateOrCreate($patientRule, $registerData['patientData']);
            if (!$patient) {
                throw new \Exception('Failed to create patient master data');
            } else {
                $patient->past_medical_procedures()->create($registerData['patientPastMedicalProcedureData']);
                $patient->past_medical_history()->create($registerData['patientPastMedicalHistoryData']);
                $patient->past_immunization()->create($registerData['patientPastImmunizationData']);
                $patient->past_bad_habits()->create($registerData['patientPastBadHabitsData']);

                $patientPriviledgeCard = $patient->privilegedCard()->create($registerData['patientPrivilegedCard']);
                $registerData['patientPrivilegedPointTransfers']['fromCard_Id'] = $patientPriviledgeCard->id;
                $registerData['patientPrivilegedPointTransfers']['toCard_Id'] = $patientPriviledgeCard->id;
                $registerData['patientPrivilegedPointTransactions']['card_Id'] = $patientPriviledgeCard->id;
                $patientPriviledgeCard->pointTransactions()->create($registerData['patientPrivilegedPointTransactions']);
                $patientPriviledgeCard->pointTransfers()->create($registerData['patientPrivilegedPointTransfers']);
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

                $allergies = $patientRegistry->allergies()->createMany($allergyResults['patientAllergyData']);
                $arrayCause = [];
                $arraySymptoms = [];
                $arrayDrugs = [];
                
                foreach ($allergies as $allergy) {
                    $assessID = $allergy->id;
                    if (!empty($allergyResults['patientCauseAllergyData'])) {
                        $cause = array_shift($allergyResults['patientCauseAllergyData']);
                        $cause['assessID'] = $assessID; 
                        $arrayCause[] = $cause; 
                    }
                    if (!empty($allergyResults['patientDrugUsedForAllergyData'])) {
                        $drug = array_shift($allergyResults['patientDrugUsedForAllergyData']);
                        $drug['assessID'] = $assessID; 
                        $arrayDrugs[] = $drug; 
                    }
                    if (!empty($allergyResults['patientSymptomsOfAllergy'])) {
                        foreach ($allergyResults['patientSymptomsOfAllergy'] as $symptom) {
                            if ($symptom['allergy_Type_Id'] == $cause['allergy_Type_Id']) { 
                                $symptom['assessID'] = $assessID; 
                                $arraySymptoms[] = $symptom; 
                            }
                        }
                    }
                }
                
                $uniqueCauses = [];
                $uniqueDrugs = [];
                $uniqueCauses = $this->getUniqueAllergy($arrayCause);
                $uniqueDrugs = $this->getUniqueAllergy($arrayDrugs);

                if (!empty($uniqueCauses)) {
                    $allergy->cause_of_allergy()->insert($uniqueCauses);
                }
                if (!empty($arraySymptoms)) {
                    $allergy->symptoms_allergy()->insert(array_values($arraySymptoms));
                }
                if (!empty($uniqueDrugs)) {
                    $allergy->drug_used_for_allergy()->insert($uniqueDrugs);
                }

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
                
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_patient_data')->commit();
                DB::connection('sqlsrv')->commit();

                return response()->json([
                    'message' => 'Outpatient data registered successfully',
                    'patient' => $patient,
                    'patientRegistry' => $patientRegistry
                ], 200);
            }

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            DB::connection('sqlsrv')->rollBack();

            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {
            $today = Carbon::now();
            $patient = Patient::where('patient_Id', $id)->first();
            $patient_id = $patient ? $patient->patient_Id : $request->payload['patient_Id'];

            $isPatientRegistered = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('registry_Date', $today)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$isPatientRegistered) {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
                SystemSequence::where('code', 'MERN')->increment('seq_no');
                SystemSequence::where('code', 'MOPD')->increment('seq_no');
                SystemSequence::where('code', 'MERN')->increment('recent_generated');
                SystemSequence::where('code', 'MOPD')->increment('recent_generated');
            }

            $medsys_registry_sequence = DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->select('OPDId')->first();

            if ($isPatientRegistered) {
                if ($this->check_is_allow_medsys) {
                    $registry_id = PatientRegistry::where('patient_Id', $patient_id)
                        ->whereDate('registry_Date', $today)
                        ->value('case_No');
                } 
            } else {
                if ($this->check_is_allow_medsys) {
                    $registry_id = $medsys_registry_sequence->OPDId;
                } 
            }

            $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id);

            $patientIdentifier  = $request->payload['patientIdentifier'] ?? null;
            $isHemodialysis     = ($patientIdentifier === "Hemo Patient") ? true : false;
            $isPeritoneal       = ($patientIdentifier === "Peritoneal Patient") ? true : false;
            $isLINAC            = ($patientIdentifier === "LINAC") ? true : false;
            $isCOBALT           = ($patientIdentifier === "COBALT") ? true : false;
            $isBloodTrans       = ($patientIdentifier === "Blood Trans Patient") ? true : false;
            $isChemotherapy     = ($patientIdentifier === "Chemo Patient") ? true : false;
            $isBrachytherapy    = ($patientIdentifier === "Brachytherapy Patient") ? true : false;
            $isDebridement      = ($patientIdentifier === "Debridement") ? true : false;
            $isTBDots           = ($patientIdentifier === "TB DOTS") ? true : false;
            $isPAD              = ($patientIdentifier === "PAD Patient") ? true : false;
            $isRadioTherapy     = ($patientIdentifier === "Radio Patient") ? true : false;

            if ($patient == null) $patient = Patient::create($registerData['patientData']);

            $pastImmunization                   = $patient->past_immunization()->whereDate('created_at', $today)->first() ?? null;
            $pastMedicalHistory                 = $patient->past_medical_history()->whereDate('created_at', $today)->first() ?? null;
            $pastMedicalProcedure               = $patient->past_medical_procedures()->whereDate('created_at', $today)->first() ?? null;

            $drugUsedForAllergy                 = $patient->drug_used_for_allergy()->whereDate('created_at', $today)->first() ?? null;
            $pastBadHabits                      = $patient->past_bad_habits()->whereDate('created_at', $today)->first() ?? null;
            $privilegedCard                     = $patient->privilegedCard()->whereDate('created_at', $today)->first() ?? null;

            $privilegedPointTransfers           = $privilegedCard && $privilegedCard->pointTransfers() 
                                                    ? $privilegedCard->pointTransfers()->whereDate('created_at', $today)->first() 
                                                    : null;
            $privilegedPointTransactions        = $privilegedCard && $privilegedCard->pointTransactions()
                                                    ? $privilegedCard->pointTransactions()->whereDate('created_at', $today)->first() 
                                                    : null;

            $dischargeInstructions              = $patient->dischargeInstructions()->whereDate('created_at', $today)->first() ?? null;
            $dischargeMedications               = $dischargeInstructions && $dischargeInstructions->dischargeMedications() 
                                                    ? $dischargeInstructions->dischargeMedications()->whereDate('created_at', $today)->first() 
                                                    : null;
            $dischargeFollowUpTreatment         = $dischargeInstructions && $dischargeInstructions->dischargeFollowUpTreatment()
                                                    ? $dischargeInstructions->dischargeFollowUpTreatment()->whereDate('created_at', $today)->first() 
                                                    : null;
            $dischargeFollowUpLaboratories      = $dischargeInstructions && $dischargeInstructions->dischargeFollowUpLaboratories() 
                                                    ? $dischargeInstructions->dischargeFollowUpLaboratories()->whereDate('created_at', $today)->first() 
                                                    : null;
            $dischargeDoctorsFollowUp           = $dischargeInstructions && $dischargeInstructions->dischargeDoctorsFollowUp()
                                                    ? $dischargeInstructions->dischargeDoctorsFollowUp()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientRegistry                    = $patient->patientRegistry()->whereDate('created_at', $today)->first() ?? null;
            $patientHistory                     = $patientRegistry && $patientRegistry->history() 
                                                    ? $patientRegistry->history()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientMedicalProcedure            = $patientRegistry && $patientRegistry->medical_procedures() 
                                                    ? $patientRegistry->medical_procedures()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientVitalSign                   = $patientRegistry && $patientRegistry->vitals() 
                                                    ? $patientRegistry->vitals()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientImmunization                = $patientRegistry && $patientRegistry->immunizations() 
                                                    ? $patientRegistry->immunizations()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientAdministeredMedicine        = $patientRegistry && $patientRegistry->administered_medicines() 
                                                    ? $patientRegistry->administered_medicines()->whereDate('created_at', $today)->first() 
                                                    : null;

            $OBGYNHistory                       = $patientRegistry && $patientRegistry->oBGYNHistory() 
                                                    ? $patientRegistry->oBGYNHistory()->whereDate('created_at', $today)->first() 
                                                    : null;
            $pregnancyHistory                   = $OBGYNHistory && $OBGYNHistory->PatientPregnancyHistory() 
                                                    ? $OBGYNHistory->PatientPregnancyHistory()->whereDate('created_at', $today)->first() 
                                                    : null;
            $gynecologicalConditions            = $OBGYNHistory && $OBGYNHistory->gynecologicalConditions()
                                                    ? $OBGYNHistory->gynecologicalConditions()->whereDate('created_at', $today)->first() 
                                                    : null;

            $allergy                            = $patientRegistry && $patientRegistry->allergies() 
                                                    ? $patientRegistry->allergies()->whereDate('created_at', $today)->first() 
                                                    : null;
            $causeOfAllergy                     = $allergy && $allergy->cause_of_allergy() 
                                                    ? $allergy->cause_of_allergy()->whereDate('created_at', $today)->first() 
                                                    : null;
            $symptomsOfAllergy                  = $allergy && $allergy->symptoms_allergy()
                                                    ? $allergy->symptoms_allergy()->whereDate('created_at', $today)->first() 
                                                    : null;

            $badHabits                          = $patientRegistry && $patientRegistry->bad_habits() 
                                                    ? $patientRegistry->bad_habits()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientDoctors                     = $patientRegistry && $patientRegistry->patientDoctors() 
                                                    ? $patientRegistry->patientDoctors()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalAbdomen                    = $patientRegistry &&  $patientRegistry->physicalAbdomen() 
                                                    ?  $patientRegistry->physicalAbdomen()->whereDate('created_at', $today)->first() 
                                                    : null;

            $pertinentSignAndSymptoms            = $patientRegistry &&  $patientRegistry->pertinentSignAndSymptoms() 
                                                    ?  $patientRegistry->pertinentSignAndSymptoms()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalExamtionChestLungs         = $patientRegistry &&  $patientRegistry->physicalExamtionChestLungs() 
                                                    ?  $patientRegistry->physicalExamtionChestLungs()->whereDate('created_at', $today)->first() 
                                                    : null;

            $courseInTheWard                    = $patientRegistry &&  $patientRegistry->courseInTheWard() 
                                                    ?  $patientRegistry->courseInTheWard()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalExamtionCVS                = $patientRegistry &&  $patientRegistry->physicalExamtionCVS() 
                                                    ?  $patientRegistry->physicalExamtionCVS()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalExamtionGeneralSurvey      = $patientRegistry && $patientRegistry->PhysicalExamtionGeneralSurvey() 
                                                    ? $patientRegistry->PhysicalExamtionGeneralSurvey()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalExamtionHEENT              = $patientRegistry && $patientRegistry->physicalExamtionHEENT() 
                                                    ? $patientRegistry->physicalExamtionHEENT()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalGUIE                       = $patientRegistry && $patientRegistry->physicalGUIE() 
                                                    ? $patientRegistry->physicalGUIE()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalNeuroExam                  = $patientRegistry && $patientRegistry->physicalNeuroExam() 
                                                    ? $patientRegistry->physicalNeuroExam()->whereDate('created_at', $today)->first() 
                                                    : null;

            $physicalSkinExtremities            = $patientRegistry && $patientRegistry->physicalSkinExtremities() 
                                                    ? $patientRegistry->physicalSkinExtremities()->whereDate('created_at', $today)->first() 
                                                    : null;

            $medications                        = $patientRegistry && $patientRegistry->medications() 
                                                    ? $patientRegistry->medications()->whereDate('created_at', $today)->first() 
                                                    : null;

            $patientData = [
                'title_id'                  => $request->payload['title_id'] ?? $patient->title_id,
                'lastname'                  => ucwords($request->payload['lastname']) ?? $patient->lastname,
                'firstname'                 => ucwords($request->payload['firstname']) ?? $patient->firstname,
                'middlename'                => ucwords($request->payload['middlename']) ?? $patient->middlename,
                'suffix_id'                 => $request->payload['suffix_id'] ?? $patient->suffix_id,
                'birthdate'                 => $request->payload['birthdate'] ?? $patient->birthdate,
                'age'                       => $request->payload['age'] ?? $patient->age,
                'sex_id'                    => $request->payload['sex_id'] ?? $patient->sex_id,
                'nationality_id'            => $request->payload['nationality_id'] ?? $patient->nationality_id,
                'religion_id'               => $request->payload['religion_id'] ?? $patient->religion_id,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? $patient->civilstatus_id,
                'typeofbirth_id'            => $request->payload['typeofbirth_id'] ?? $patient->typeofbirth_id,
                'birthtime'                 => $request->payload['birthtime'] ?? $patient->birthtime,
                'birthplace'                => $request->payload['birthplace'] ?? $patient->birthplace, 
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? $patient->typeofdeath_id,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? $patient->timeofdeath,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? $patient->bloodtype_id,
                'bldgstreet'                => $request->payload['address']['bldgstreet'] ?? $patient->bldgstreet,
                'region_id'                 => $request->payload['address']['region_id'] ?? $patient->region_id,
                'province_id'               => $request->payload['address']['province_id'] ?? $patient->province_id,
                'municipality_id'           => $request->payload['address']['municipality_id'] ?? $patient->municipality_id,
                'barangay_id'               => $request->payload['address']['barangay_id'] ?? $patient->barangay_id,
                'zipcode_id'                => $request->payload['address']['zipcode_id'] ?? $patient->zipcode_id,
                'country_id'                => $request->payload['address']['country_id'] ?? $patient->country_id,
                'occupation'                => $request->payload['occupation'] ?? $patient->occupation,
                'telephone_number'          => $request->payload['telephone_number'] ?? $patient->telephone_number,
                'mobile_number'             => $request->payload['mobile_number'] ?? $patient->mobile_number,
                'email_address'             => $request->payload['email_address'] ?? $patient->email_address,
                'isSeniorCitizen'           => $request->payload['isSeniorCitizen'] ?? $patient->isSeniorCitizen,
                'SeniorCitizen_ID_Number'   => $request->payload['SeniorCitizen_ID_Number'] ?? $patient->SeniorCitizen_ID_Number,
                'isPWD'                     => $request->payload['isPWD'] ?? $patient->isPWD,
                'PWD_ID_Number'             => $request->payload['PWD_ID_Number'] ?? $patient->PWD_ID_Number,
                'isPhilhealth_Member'       => $request->payload['isPhilhealth_Member'] ?? $patient->isPhilhealth_Member,
                'Philhealth_Number'         => $request->payload['Philhealth_Number'] ?? $patient->Philhealth_Number,
                'isEmployee'                => $request->payload['isEmployee'] ?? $patient->isEmployee,
                'GSIS_Number'               => $request->payload['GSIS_Number'] ?? $patient->GSIS_Number,
                'SSS_Number'                => $request->payload['SSS_Number'] ?? $patient->SSS_Number,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? $patient->is_old_patient,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? $patient->portal_access_uid,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? $patient->portal_access_pwd,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? $patient->isBlacklisted,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? $patient->blacklist_reason,
                'isAbscond'                 => $request->payload['isAbscond'] ?? $patient->isAbscond,
                'abscond_details'           => $request->payload['abscond_details'] ?? $patient->abscond_details,
                'dialect_spoken'            => $request->payload['dialect_spoken'] ?? $patient->dialect_spoken,
                'motherLastname'            => $request->payload['motherLastname'] ?? $patient->motherLastname,
                'motherFirstname'           => $request->payload['motherFirstname'] ?? $patient->motherFirstname,
                'motherMiddlename'          => $request->payload['motherMiddlename'] ?? $patient->motherMiddlename,
                'motherSuffix_id'           => $request->payload['motherSuffix_id'] ?? $patient->motherSuffix_id,
                'mothertelephone_number'    => $request->payload['mothertelephone_number'] ?? $patient->mothertelephone_number,
                'mothermobile_number'       => $request->payload['mothermobile_number'] ?? $patient->mothermobile_number,
                'motherAddress'             => $request->payload['motherAddress'] ?? $patient->motherAddress,
                'fatherLastname'            => $request->payload['fatherLastname'] ?? $patient->fatherLastname,
                'fatherFirstname'           => $request->payload['fatherFirstname'] ?? $patient->fatherFirstname,
                'fatherMiddlename'          => $request->payload['fatherMiddlename'] ?? $patient->fatherMiddlename,
                'fatherSuffix_id'           => $request->payload['fatherSuffix_id'] ?? $patient->fatherSuffix_id,
                'fathertelephone_number'    => $request->payload['fathertelephone_number'] ?? $patient->fathertelephone_number,
                'fathermobile_number'       => $request->payload['fathermobile_number'] ?? $patient->fathermobile_number,
                'fatherAddress'             => $request->payload['fatherAddress'] ?? $patient->fatherAddress,
                'spLastname'                => $request->payload['spLastname'] ?? $patient->spLastname,
                'spFirstname'               => $request->payload['spFirstname'] ?? $patient->spFirstname,
                'spMiddlename'              => $request->payload['spMiddlename'] ?? $patient->spMiddlename,
                'spSuffix_id'               => $request->payload['spSuffix_id'] ?? $patient->spSuffix_id,
                'sptelephone_number'        => $request->payload['sptelephone_number'] ?? $patient->sptelephone_number,
                'spmobile_number'           => $request->payload['spmobile_number'] ?? $patient->spmobile_number,
                'spAddress'                 => $request->payload['spAddress'] ?? $patient->spAddress,
                'updatedBy'                 => Auth()->user()->idnumber,
                'updated_at'                => $today
            ];

            $patientPastImmunizationData = [
                'branch_Id'             => 1,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? ($pastImmunization->vaccine_Id ?? null),
                'administration_Date'   => $request->payload['administration_Date'] ?? ($pastImmunization->administration_Date ?? null),
                'dose'                  => $request->payload['dose'] ?? ($pastImmunization->dose ?? null),
                'site'                  => $request->payload['site'] ?? ($pastImmunization->site ?? null),
                'administrator_Name'    => $request->payload['administrator_Name'] ?? ($pastImmunization->administrator_Name ?? null),
                'notes'                 => $request->payload['notes'] ?? ($pastImmunization->notes ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPastMedicalHistoryData = [
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? ($pastMedicalHistory->diagnose_Description ?? null),
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? ($pastMedicalHistory->diagnosis_Date ?? null),
                'treament'                  => $request->payload['treament'] ?? ($pastMedicalHistory->treament ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPastMedicalProcedureData = [
                'description'               => $request->payload['description'] ?? ($pastMedicalProcedure->description ?? null),
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ?? ($pastMedicalProcedure->date_Of_Procedure ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientBadHabitsData = [
                'description'   => $request->payload['description'] ?? ($badHabits->description ?? null),
                'updatedby'     => Auth()->user()->idnumber,
                'updated_at'    => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'description'   => $request->payload['description'] ?? ($pastBadHabits->description ?? null),
                'updatedby'     => Auth()->user()->idnumber,
                'updated_at'    => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'drug_Description'  => $request->payload['drug_Description'] ?? ($drugUsedForAllergy->drug_Description ?? null),
                'hospital'          => $request->payload['hospital'] ?? ($drugUsedForAllergy->hospital ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientDoctorsData = [
                'doctor_Id'         => $request->payload['doctor_Id'] ?? ($patientDoctors->doctor_Id ?? null),
                'doctors_Fullname'  => $request->payload['doctors_Fullname'] ?? ($patientDoctors->doctors_Fullname ?? null),
                'role_Id'           => $request->payload['role_Id'] ?? ($patientDoctors->role_Id ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientPhysicalAbdomenData = [
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? ($physicalAbdomen->essentially_Normal ?? null),
                'palpable_Masses'           => $request->payload['palpable_Masses'] ?? ($physicalAbdomen->palpable_Masses ?? null),
                'abdominal_Rigidity'        => $request->payload['abdominal_Rigidity'] ?? ($physicalAbdomen->abdominal_Rigidity ?? null),
                'uterine_Contraction'       => $request->payload['uterine_Contraction'] ?? ($physicalAbdomen->uterine_Contraction ?? null),
                'hyperactive_Bowel_Sounds'  => $request->payload['hyperactive_Bowel_Sounds'] ?? ($physicalAbdomen->hyperactive_Bowel_Sounds ?? null),
                'others_Description'        => $request->payload['others_Description'] ?? ($physicalAbdomen->others_Description ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPertinentSignAndSymptomsData = [
                'altered_Mental_Sensorium'          => $request->payload['altered_Mental_Sensorium'] ?? ($pertinentSignAndSymptoms->altered_Mental_Sensorium ?? null),
                'abdominal_CrampPain'               => $request->payload['abdominal_CrampPain'] ?? ($pertinentSignAndSymptoms->abdominal_CrampPain ?? null),
                'anorexia'                          => $request->payload['anorexia'] ?? ($pertinentSignAndSymptoms->anorexia ?? null),
                'bleeding_Gums'                     => $request->payload['bleeding_Gums'] ?? ($pertinentSignAndSymptoms->bleeding_Gums ?? null),
                'body_Weakness'                     => $request->payload['body_Weakness'] ?? ($pertinentSignAndSymptoms->body_Weakness ?? null),
                'blurring_Of_Vision'                => $request->payload['blurring_Of_Vision'] ?? ($pertinentSignAndSymptoms->blurring_Of_Vision ?? null),
                'chest_PainDiscomfort'              => $request->payload['chest_PainDiscomfort'] ?? ($pertinentSignAndSymptoms->chest_PainDiscomfort ?? null),
                'constipation'                      => $request->payload['constipation'] ?? ($pertinentSignAndSymptoms->constipation ?? null),
                'cough'                             => $request->payload['cough'] ?? ($pertinentSignAndSymptoms->cough ?? null),
                'diarrhea'                          => $request->payload['diarrhea'] ?? ($pertinentSignAndSymptoms->diarrhea ?? null),
                'dizziness'                         => $request->payload['dizziness'] ?? ($pertinentSignAndSymptoms->dizziness ?? null),
                'dysphagia'                         => $request->payload['dysphagia'] ?? ($pertinentSignAndSymptoms->dysphagia ?? null),
                'dysuria'                           => $request->payload['dysuria'] ?? ($pertinentSignAndSymptoms->dysuria ?? null),
                'epistaxis'                         => $request->payload['epistaxis'] ?? ($pertinentSignAndSymptoms->epistaxis ?? null),
                'fever'                             => $request->payload['fever'] ?? ($pertinentSignAndSymptoms->fever ?? null),
                'frequency_Of_Urination'            => $request->payload['frequency_Of_Urination'] ?? ($pertinentSignAndSymptoms->frequency_Of_Urination ?? null),
                'headache'                          => $request->payload['headache'] ?? ($pertinentSignAndSymptoms->headache ?? null),
                'hematemesis'                       => $request->payload['hematemesis'] ?? ($pertinentSignAndSymptoms->hematemesis ?? null),
                'hematuria'                         => $request->payload['hematuria'] ?? ($pertinentSignAndSymptoms->hematuria ?? null),
                'hemoptysis'                        => $request->payload['hemoptysis'] ?? ($pertinentSignAndSymptoms->hemoptysis ?? null),
                'irritability'                      => $request->payload['irritability'] ?? ($pertinentSignAndSymptoms->irritability ?? null),
                'jaundice'                          => $request->payload['jaundice'] ?? ($pertinentSignAndSymptoms->jaundice ?? null),
                'lower_Extremity_Edema'             => $request->payload['lower_Extremity_Edema'] ?? ($pertinentSignAndSymptoms->lower_Extremity_Edema ?? null),
                'myalgia'                           => $request->payload['myalgia'] ?? ($pertinentSignAndSymptoms->myalgia ?? null),
                'orthopnea'                         => $request->payload['orthopnea'] ?? ($pertinentSignAndSymptoms->orthopnea ?? null),
                'pain'                              => $request->payload['pain'] ?? ($pertinentSignAndSymptoms->pain ?? null),
                'palpitations'                      => $request->payload['palpitations'] ?? ($pertinentSignAndSymptoms->palpitations ?? null),
                'seizures'                          => $request->payload['seizures'] ?? ($pertinentSignAndSymptoms->seizures ?? null),
                'skin_rashes'                       => $request->payload['skin_rashes'] ?? ($pertinentSignAndSymptoms->skin_rashes ?? null),
                'stool_BloodyBlackTarry_Mucoid'     => $request->payload['stool_BloodyBlackTarry_Mucoid'] ?? ($pertinentSignAndSymptoms->stool_BloodyBlackTarry_Mucoid ?? null),
                'sweating'                          => $request->payload['sweating'] ?? ($pertinentSignAndSymptoms->sweating ?? null),
                'urgency'                           => $request->payload['urgency'] ?? ($pertinentSignAndSymptoms->urgency ?? null),
                'vomitting'                         => $request->payload['vomitting'] ?? ($pertinentSignAndSymptoms->vomitting ?? null),
                'weightloss'                        => $request->payload['weightloss'] ?? ($pertinentSignAndSymptoms->weightloss ?? null),
                'others'                            => $request->payload['others'] ?? ($pertinentSignAndSymptoms->others ?? null),
                'others_Description'                => $request->payload['others_Description'] ?? ($pertinentSignAndSymptoms->others_Description ?? null),
                'updatedby'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now(),
            ];

            $patientPhysicalExamtionChestLungsData = [
                'essentially_Normal'                    => $request->payload['essentially_Normal'] ?? ($physicalExamtionChestLungs->essentially_Normal ?? null),
                'lumps_Over_Breasts'                    => $request->payload['lumps_Over_Breasts'] ?? ($physicalExamtionChestLungs->lumps_Over_Breasts ?? null),
                'asymmetrical_Chest_Expansion'          => $request->payload['asymmetrical_Chest_Expansion'] ?? ($physicalExamtionChestLungs->asymmetrical_Chest_Expansion ?? null),
                'rales_Crackles_Rhonchi'                => $request->payload['rales_Crackles_Rhonchi'] ?? ($physicalExamtionChestLungs->rales_Crackles_Rhonchi ?? null),
                'decreased_Breath_Sounds'               => $request->payload['decreased_Breath_Sounds'] ?? ($physicalExamtionChestLungs->decreased_Breath_Sounds ?? null),
                'intercostalrib_Clavicular_Retraction'  => $request->payload['intercostalrib_Clavicular_Retraction'] ?? ($physicalExamtionChestLungs->intercostalrib_Clavicular_Retraction ?? null),
                'wheezes'                               => $request->payload['wheezes'] ?? ($physicalExamtionChestLungs->wheezes ?? null),
                'others_Description'                    => $request->payload['others_Description'] ?? ($physicalExamtionChestLungs->others_Description ?? null),
                'updatedby'                             => Auth()->user()->idnumber,
                'updated_at'                            => Carbon::now(),
            ];

            $patientCourseInTheWardData = [
                'doctors_OrdersAction'                  => $request->payload['doctors_OrdersAction'] ?? ($courseInTheWard->doctors_OrdersAction ?? null),
                'updatedby'                             => Auth()->user()->idnumber,
                'updated_at'                            => Carbon::now(),
            ];

            $patientPhysicalExamtionCVSData = [
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? ($physicalExamtionCVS->essentially_Normal ?? null),
                'irregular_Rhythm'          => $request->payload['irregular_Rhythm'] ?? ($physicalExamtionCVS->irregular_Rhythm ?? null),
                'displaced_Apex_Beat'       => $request->payload['displaced_Apex_Beat'] ?? ($physicalExamtionCVS->displaced_Apex_Beat ?? null),
                'muffled_Heart_Sounds'      => $request->payload['muffled_Heart_Sounds'] ?? ($physicalExamtionCVS->muffled_Heart_Sounds ?? null),
                'heaves_AndOR_Thrills'      => $request->payload['heaves_AndOR_Thrills'] ?? ($physicalExamtionCVS->heaves_AndOR_Thrills ?? null),
                'murmurs'                   => $request->payload['murmurs'] ?? ($physicalExamtionCVS->murmurs ?? null),
                'pericardial_Bulge'         => $request->payload['pericardial_Bulge'] ?? ($physicalExamtionCVS->pericardial_Bulge ?? null),
                'others_Description'        => $request->payload['others_Description'] ?? ($physicalExamtionCVS->others_Description ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? ($physicalExamtionGeneralSurvey->awake_And_Alert ?? null),
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? ($physicalExamtionGeneralSurvey->altered_Sensorium ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPhysicalExamtionHEENTData = [
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? ($physicalExamtionHEENT->essentially_Normal ?? null),
                'icteric_Sclerae'               => $request->payload['icteric_Sclerae'] ?? ($physicalExamtionHEENT->icteric_Sclerae ?? null),
                'abnormal_Pupillary_Reaction'   => $request->payload['abnormal_Pupillary_Reaction'] ?? ($physicalExamtionHEENT->abnormal_Pupillary_Reaction ?? null),
                'pale_Conjunctive'              => $request->payload['pale_Conjunctive'] ?? ($physicalExamtionHEENT->pale_Conjunctive ?? null),
                'cervical_Lympadenopathy'       => $request->payload['cervical_Lympadenopathy'] ?? ($physicalExamtionHEENT->cervical_Lympadenopathy ?? null),
                'sunken_Eyeballs'               => $request->payload['sunken_Eyeballs'] ?? ($physicalExamtionHEENT->sunken_Eyeballs ?? null),
                'dry_Mucous_Membrane'           => $request->payload['dry_Mucous_Membrane'] ?? ($physicalExamtionHEENT->dry_Mucous_Membrane ?? null),
                'sunken_Fontanelle'             => $request->payload['sunken_Fontanelle'] ?? ($physicalExamtionHEENT->sunken_Fontanelle ?? null),
                'others_description'            => $request->payload['others_description'] ?? ($physicalExamtionHEENT->others_description ?? null),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalGUIEData = [
                'essentially_Normal'                => $request->payload['essentially_Normal'] ?? ($physicalGUIE->essentially_Normal ?? null),
                'blood_StainedIn_Exam_Finger'       => $request->payload['blood_StainedIn_Exam_Finger'] ?? ($physicalGUIE->blood_StainedIn_Exam_Finger ?? null),
                'cervical_Dilatation'               => $request->payload['cervical_Dilatation'] ?? ($physicalGUIE->cervical_Dilatation ?? null),
                'presence_Of_AbnormalDischarge'     => $request->payload['presence_Of_AbnormalDischarge'] ?? ($physicalGUIE->presence_Of_AbnormalDischarge ?? null),
                'others_Description'                => $request->payload['others_Description'] ?? ($physicalGUIE->others_Description ?? null),
                'updated_at'                        => Carbon::now(),
            ];

            $patientPhysicalNeuroExamData = [
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? ($physicalNeuroExam->essentially_Normal ?? null),
                'abnormal_Reflexes'             => $request->payload['abnormal_Reflexes'] ?? ($physicalNeuroExam->abnormal_Reflexes ?? null),
                'abormal_Gait'                  => $request->payload['abormal_Gait'] ?? ($physicalNeuroExam->abormal_Gait ?? null),
                'poor_Altered_Memory'           => $request->payload['poor_Altered_Memory'] ?? ($physicalNeuroExam->poor_Altered_Memory ?? null),
                'abnormal_Position_Sense'       => $request->payload['abnormal_Position_Sense'] ?? ($physicalNeuroExam->abnormal_Position_Sense ?? null),
                'poor_Muscle_Tone_Strength'     => $request->payload['poor_Muscle_Tone_Strength'] ?? ($physicalNeuroExam->poor_Muscle_Tone_Strength ?? null),
                'abnormal_Decreased_Sensation'  => $request->payload['abnormal_Decreased_Sensation'] ?? ($physicalNeuroExam->abnormal_Decreased_Sensation ?? null),
                'poor_Coordination'             => $request->payload['poor_Coordination'] ?? ($physicalNeuroExam->poor_Coordination ?? null),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalSkinExtremitiesData = [
                'essentially_Normal'        => $request->payload['poor_Coordination'] ?? ($physicalSkinExtremities->poor_Coordination ?? null),
                'edema_Swelling'            => $request->payload['edema_Swelling'] ?? ($physicalSkinExtremities->edema_Swelling ?? null),
                'rashes_Petechiae'          => $request->payload['rashes_Petechiae'] ?? ($physicalSkinExtremities->rashes_Petechiae ?? null),
                'clubbing'                  => $request->payload['clubbing'] ?? ($physicalSkinExtremities->clubbing ?? null),
                'decreased_Mobility'        => $request->payload['decreased_Mobility'] ?? ($physicalSkinExtremities->decreased_Mobility ?? null),
                'weak_Pulses'               => $request->payload['weak_Pulses'] ?? ($physicalSkinExtremities->weak_Pulses ?? null),
                'cold_Clammy_Skin'          => $request->payload['cold_Clammy_Skin'] ?? ($physicalSkinExtremities->cold_Clammy_Skin ?? null),
                'pale_Nailbeds'             => $request->payload['pale_Nailbeds'] ?? ($physicalSkinExtremities->pale_Nailbeds ?? null),
                'cyanosis_Mottled_Skin'     => $request->payload['cyanosis_Mottled_Skin'] ?? ($physicalSkinExtremities->cyanosis_Mottled_Skin ?? null),
                'poor_Skin_Turgor'          => $request->payload['poor_Skin_Turgor'] ?? ($physicalSkinExtremities->poor_Skin_Turgor ?? null),
                'others_Description'        => $request->payload['others_Description'] ?? ($physicalSkinExtremities->others_Description ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];
            
            $patientOBGYNHistoryData = [
                'obsteric_Code'                                         => $request->payload['others_Description'] ?? ($OBGYNHistory->others_Description ?? null),
                'menarchAge'                                            => $request->payload['menarchAge'] ?? ($OBGYNHistory->menarchAge ?? null),
                'menopauseAge'                                          => $request->payload['menopauseAge'] ?? ($OBGYNHistory->menopauseAge ?? null),
                'cycleLength'                                           => $request->payload['cycleLength'] ?? ($OBGYNHistory->cycleLength ?? null),
                'cycleRegularity'                                       => $request->payload['cycleRegularity'] ?? ($OBGYNHistory->cycleRegularity ?? null),
                'lastMenstrualPeriod'                                   => $request->payload['lastMenstrualPeriod'] ?? ($OBGYNHistory->lastMenstrualPeriod ?? null),
                'contraceptiveUse'                                      => $request->payload['contraceptiveUse'] ?? ($OBGYNHistory->contraceptiveUse ?? null),
                'lastPapSmearDate'                                      => $request->payload['lastPapSmearDate'] ?? ($OBGYNHistory->lastPapSmearDate ?? null),
                'isVitalSigns_Normal'                                   => $request->payload['isVitalSigns_Normal'] ?? ($OBGYNHistory->isVitalSigns_Normal ?? null),
                'isAscertainPresent_PregnancyisLowRisk'                 => $request->payload['isAscertainPresent_PregnancyisLowRisk'] ?? ($OBGYNHistory->isAscertainPresent_PregnancyisLowRisk ?? null),
                'riskfactor_MultiplePregnancy'                          => $request->payload['riskfactor_MultiplePregnancy'] ?? ($OBGYNHistory->riskfactor_MultiplePregnancy ?? null),
                'riskfactor_OvarianCyst'                                => $request->payload['riskfactor_OvarianCyst'] ?? ($OBGYNHistory->riskfactor_OvarianCyst ?? null),
                'riskfactor_MyomaUteri'                                 => $request->payload['riskfactor_MyomaUteri'] ?? ($OBGYNHistory->riskfactor_MyomaUteri ?? null),
                'riskfactor_PlacentaPrevia'                             => $request->payload['riskfactor_PlacentaPrevia'] ?? ($OBGYNHistory->riskfactor_PlacentaPrevia ?? null),
                'riskfactor_Historyof3Miscarriages'                     => $request->payload['riskfactor_Historyof3Miscarriages'] ?? ($OBGYNHistory->riskfactor_Historyof3Miscarriages ?? null),
                'riskfactor_HistoryofStillbirth'                        => $request->payload['riskfactor_HistoryofStillbirth'] ?? ($OBGYNHistory->riskfactor_HistoryofStillbirth ?? null),
                'riskfactor_HistoryofEclampsia'                         => $request->payload['riskfactor_HistoryofEclampsia'] ?? ($OBGYNHistory->riskfactor_HistoryofEclampsia ?? null),
                'riskfactor_PrematureContraction'                       => $request->payload['riskfactor_PrematureContraction'] ?? ($OBGYNHistory->riskfactor_PrematureContraction ?? null),
                'riskfactor_NotApplicableNone'                          => $request->payload['riskfactor_NotApplicableNone'] ?? ($OBGYNHistory->riskfactor_NotApplicableNone ?? null),
                'medicalSurgical_Hypertension'                          => $request->payload['medicalSurgical_Hypertension'] ?? ($OBGYNHistory->medicalSurgical_Hypertension ?? null),
                'medicalSurgical_HeartDisease'                          => $request->payload['medicalSurgical_HeartDisease'] ?? ($OBGYNHistory->medicalSurgical_HeartDisease ?? null),
                'medicalSurgical_Diabetes'                              => $request->payload['medicalSurgical_Diabetes'] ?? ($OBGYNHistory->medicalSurgical_Diabetes ?? null),
                'medicalSurgical_ThyroidDisorder'                       => $request->payload['medicalSurgical_ThyroidDisorder'] ?? ($OBGYNHistory->medicalSurgical_ThyroidDisorder ?? null),
                'medicalSurgical_Obesity'                               => $request->payload['medicalSurgical_Obesity'] ?? ($OBGYNHistory->medicalSurgical_Obesity ?? null),
                'medicalSurgical_ModerateToSevereAsthma'                => $request->payload['medicalSurgical_ModerateToSevereAsthma'] ?? ($OBGYNHistory->medicalSurgical_ModerateToSevereAsthma ?? null),
                'medicalSurigcal_Epilepsy'                              => $request->payload['medicalSurigcal_Epilepsy'] ?? ($OBGYNHistory->medicalSurigcal_Epilepsy ?? null),
                'medicalSurgical_RenalDisease'                          => $request->payload['medicalSurgical_RenalDisease'] ?? ($OBGYNHistory->medicalSurgical_RenalDisease ?? null),
                'medicalSurgical_BleedingDisorder'                      => $request->payload['medicalSurgical_BleedingDisorder'] ?? ($OBGYNHistory->medicalSurgical_BleedingDisorder ?? null),
                'medicalSurgical_HistoryOfPreviousCesarianSection'      => $request->payload['medicalSurgical_HistoryOfPreviousCesarianSection'] ?? ($OBGYNHistory->medicalSurgical_HistoryOfPreviousCesarianSection ?? null),
                'medicalSurgical_HistoryOfUterineMyomectomy'            => $request->payload['medicalSurgical_HistoryOfUterineMyomectomy'] ?? ($OBGYNHistory->medicalSurgical_HistoryOfUterineMyomectomy ?? null),
                'medicalSurgical_NotApplicableNone'                     => $request->payload['medicalSurgical_NotApplicableNone'] ?? ($OBGYNHistory->medicalSurgical_NotApplicableNone ?? null),
                'deliveryPlan_OrientationToMCP'                         => $request->payload['deliveryPlan_OrientationToMCP'] ?? ($OBGYNHistory->deliveryPlan_OrientationToMCP ?? null),
                'deliveryPlan_ExpectedDeliveryDate'                     => $request->payload['deliveryPlan_ExpectedDeliveryDate'] ?? ($OBGYNHistory->deliveryPlan_ExpectedDeliveryDate ?? null),
                'followUp_Prenatal_ConsultationNo_2nd'                  => $request->payload['followUp_Prenatal_ConsultationNo_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_2nd ?? null),
                'followUp_Prenatal_DateVisit_2nd'                       => $request->payload['followUp_Prenatal_DateVisit_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_2nd ?? null),
                'followUp_Prenatal_AOGInWeeks_2nd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_2nd ?? null),
                'followUp_Prenatal_Weight_2nd'                          => $request->payload['followUp_Prenatal_Weight_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_2nd ?? null),
                'followUp_Prenatal_CardiacRate_2nd'                     => $request->payload['followUp_Prenatal_CardiacRate_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_2nd ?? null),
                'followUp_Prenatal_RespiratoryRate_2nd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_2nd ?? null),
                'followUp_Prenatal_BloodPresureSystolic_2nd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_2nd ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_2nd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_2nd ?? null),
                'followUp_Prenatal_Temperature_2nd'                     => $request->payload['followUp_Prenatal_Temperature_2nd'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_2nd ?? null),
                'followUp_Prenatal_ConsultationNo_3rd'                  => $request->payload['followUp_Prenatal_ConsultationNo_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_3rd ?? null),
                'followUp_Prenatal_DateVisit_3rd'                       => $request->payload['followUp_Prenatal_DateVisit_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_3rd ?? null),
                'followUp_Prenatal_AOGInWeeks_3rd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_3rd ?? null),
                'followUp_Prenatal_Weight_3rd'                          => $request->payload['followUp_Prenatal_Weight_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_3rd ?? null),
                'followUp_Prenatal_CardiacRate_3rd'                     => $request->payload['followUp_Prenatal_CardiacRate_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_3rd ?? null),
                'followUp_Prenatal_RespiratoryRate_3rd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_3rd ?? null),
                'followUp_Prenatal_BloodPresureSystolic_3rd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_3rd ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_3rd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_3rd ?? null),
                'followUp_Prenatal_Temperature_3rd'                     => $request->payload['followUp_Prenatal_Temperature_3rd'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_3rd ?? null),
                'followUp_Prenatal_ConsultationNo_4th'                  => $request->payload['followUp_Prenatal_ConsultationNo_4th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_4th ?? null),
                'followUp_Prenatal_DateVisit_4th'                       => $request->payload['followUp_Prenatal_DateVisit_4th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_4th ?? null),
                'followUp_Prenatal_AOGInWeeks_4th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_4th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_4th ?? null),
                'followUp_Prenatal_Weight_4th'                          => $request->payload['followUp_Prenatal_Weight_4th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_4th ?? null),
                'followUp_Prenatal_CardiacRate_4th'                     => $request->payload['followUp_Prenatal_CardiacRate_4th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_4th ?? null),
                'followUp_Prenatal_RespiratoryRate_4th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_4th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_4th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_4th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_4th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_4th ?? null),
                'followUp_Prenatal_ConsultationNo_5th'                  => $request->payload['followUp_Prenatal_ConsultationNo_5th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_5th ?? null),
                'followUp_Prenatal_DateVisit_5th'                       => $request->payload['followUp_Prenatal_DateVisit_5th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_5th ?? null),
                'followUp_Prenatal_AOGInWeeks_5th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_5th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_5th ?? null),
                'followUp_Prenatal_Weight_5th'                          => $request->payload['followUp_Prenatal_Weight_5th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_5th ?? null),
                'followUp_Prenatal_CardiacRate_5th'                     => $request->payload['followUp_Prenatal_CardiacRate_5th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_5th ?? null),
                'followUp_Prenatal_RespiratoryRate_5th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_5th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_5th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_5th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_5th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_5th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_5th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_5th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_5th ?? null),
                'followUp_Prenatal_Temperature_5th'                     => $request->payload['followUp_Prenatal_Temperature_5th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_5th ?? null),
                'followUp_Prenatal_ConsultationNo_6th'                  => $request->payload['followUp_Prenatal_ConsultationNo_6th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_6th ?? null),
                'followUp_Prenatal_DateVisit_6th'                       => $request->payload['followUp_Prenatal_DateVisit_6th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_6th ?? null),
                'followUp_Prenatal_AOGInWeeks_6th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_6th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_6th ?? null),
                'followUp_Prenatal_Weight_6th'                          => $request->payload['followUp_Prenatal_Weight_6th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_6th ?? null),
                'followUp_Prenatal_CardiacRate_6th'                     => $request->payload['followUp_Prenatal_CardiacRate_6th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_6th ?? null),
                'followUp_Prenatal_RespiratoryRate_6th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_6th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_6th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_6th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_6th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_6th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_6th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_6th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_6th ?? null),
                'followUp_Prenatal_Temperature_6th'                     => $request->payload['followUp_Prenatal_Temperature_6th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_6th ?? null),
                'followUp_Prenatal_ConsultationNo_7th'                  => $request->payload['followUp_Prenatal_ConsultationNo_7th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_7th ?? null),
                'followUp_Prenatal_DateVisit_7th'                       => $request->payload['followUp_Prenatal_DateVisit_7th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_7th ?? null),
                'followUp_Prenatal_AOGInWeeks_7th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_7th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_7th ?? null),
                'followUp_Prenatal_Weight_7th'                          => $request->payload['followUp_Prenatal_Weight_7th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_7th ?? null),
                'followUp_Prenatal_CardiacRate_7th'                     => $request->payload['followUp_Prenatal_CardiacRate_7th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_7th ?? null),
                'followUp_Prenatal_RespiratoryRate_7th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_7th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_7th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_7th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_7th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_7th ?? null),
                'followUp_Prenatal_Temperature_7th'                     => $request->payload['followUp_Prenatal_Temperature_7th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_7th ?? null),
                'followUp_Prenatal_ConsultationNo_8th'                  => $request->payload['followUp_Prenatal_ConsultationNo_8th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_8th ?? null),
                'followUp_Prenatal_DateVisit_8th'                       => $request->payload['followUp_Prenatal_DateVisit_8th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_8th ?? null),
                'followUp_Prenatal_AOGInWeeks_8th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_8th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_8th ?? null),
                'followUp_Prenatal_Weight_8th'                          => $request->payload['followUp_Prenatal_Weight_8th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_8th ?? null),
                'followUp_Prenatal_CardiacRate_8th'                     => $request->payload['followUp_Prenatal_CardiacRate_8th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_8th ?? null),
                'followUp_Prenatal_RespiratoryRate_8th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_8th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_8th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_8th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_8th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_8th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_8th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_8th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_8th ?? null),
                'followUp_Prenatal_Temperature_8th'                     => $request->payload['followUp_Prenatal_Temperature_8th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_8th ?? null),
                'followUp_Prenatal_ConsultationNo_9th'                  => $request->payload['followUp_Prenatal_ConsultationNo_9th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_9th ?? null),
                'followUp_Prenatal_DateVisit_9th'                       => $request->payload['followUp_Prenatal_DateVisit_9th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_9th ?? null),
                'followUp_Prenatal_AOGInWeeks_9th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_9th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_9th ?? null),
                'followUp_Prenatal_Weight_9th'                          => $request->payload['followUp_Prenatal_Weight_9th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_9th ?? null),
                'followUp_Prenatal_CardiacRate_9th'                     => $request->payload['followUp_Prenatal_CardiacRate_9th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_9th ?? null),
                'followUp_Prenatal_RespiratoryRate_9th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_9th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_9th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_9th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_9th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_9th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_9th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_9th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_9th ?? null),
                'followUp_Prenatal_Temperature_9th'                     => $request->payload['followUp_Prenatal_Temperature_9th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_9th ?? null),
                'followUp_Prenatal_ConsultationNo_10th'                 => $request->payload['followUp_Prenatal_ConsultationNo_10th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_10th ?? null),
                'followUp_Prenatal_DateVisit_10th'                      => $request->payload['followUp_Prenatal_DateVisit_10th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_10th ?? null),
                'followUp_Prenatal_AOGInWeeks_10th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_10th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_10th ?? null),
                'followUp_Prenatal_Weight_10th'                         => $request->payload['followUp_Prenatal_Weight_10th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_10th ?? null),
                'followUp_Prenatal_CardiacRate_10th'                    => $request->payload['followUp_Prenatal_CardiacRate_10th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_10th ?? null),
                'followUp_Prenatal_RespiratoryRate_10th'                => $request->payload['followUp_Prenatal_RespiratoryRate_10th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_10th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_10th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_10th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_10th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_10th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_10th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_10th ?? null),
                'followUp_Prenatal_Temperature_10th'                    => $request->payload['followUp_Prenatal_Temperature_10th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_10th ?? null),
                'followUp_Prenatal_ConsultationNo_11th'                 => $request->payload['followUp_Prenatal_ConsultationNo_11th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_11th ?? null),
                'followUp_Prenatal_DateVisit_11th'                      => $request->payload['followUp_Prenatal_DateVisit_11th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_11th ?? null),
                'followUp_Prenatal_AOGInWeeks_11th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_11th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_11th ?? null),
                'followUp_Prenatal_Weight_11th'                         => $request->payload['followUp_Prenatal_Weight_11th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_11th ?? null),
                'followUp_Prenatal_CardiacRate_11th'                    => $request->payload['followUp_Prenatal_CardiacRate_11th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_11th ?? null),
                'followUp_Prenatal_RespiratoryRate_11th'                => $request->payload['followUp_Prenatal_RespiratoryRate_11th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_11th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_11th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_11th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_11th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_11th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_11th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_11th ?? null),
                'followUp_Prenatal_Temperature_11th'                    => $request->payload['followUp_Prenatal_Temperature_11th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_11th ?? null),
                'followUp_Prenatal_ConsultationNo_12th'                 => $request->payload['followUp_Prenatal_ConsultationNo_12th'] ?? ($OBGYNHistory->followUp_Prenatal_ConsultationNo_12th ?? null),
                'followUp_Prenatal_DateVisit_12th'                      => $request->payload['followUp_Prenatal_DateVisit_12th'] ?? ($OBGYNHistory->followUp_Prenatal_DateVisit_12th ?? null),
                'followUp_Prenatal_AOGInWeeks_12th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_12th'] ?? ($OBGYNHistory->followUp_Prenatal_AOGInWeeks_12th ?? null),
                'followUp_Prenatal_Weight_12th'                         => $request->payload['followUp_Prenatal_Weight_12th'] ?? ($OBGYNHistory->followUp_Prenatal_Weight_12th ?? null),
                'followUp_Prenatal_CardiacRate_12th'                    => $request->payload['followUp_Prenatal_CardiacRate_12th'] ?? ($OBGYNHistory->followUp_Prenatal_CardiacRate_12th ?? null),
                'followUp_Prenatal_RespiratoryRate_12th'                => $request->payload['followUp_Prenatal_RespiratoryRate_12th'] ?? ($OBGYNHistory->followUp_Prenatal_RespiratoryRate_12th ?? null),
                'followUp_Prenatal_BloodPresureSystolic_12th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_12th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_12th ?? null),
                'followUp_Prenatal_BloodPresureDiastolic_12th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_12th'] ?? ($OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_12th ?? null),
                'followUp_Prenatal_Temperature_12th'                    => $request->payload['followUp_Prenatal_Temperature_12th'] ?? ($OBGYNHistory->followUp_Prenatal_Temperature_12th ?? null),
                'followUp_Prenatal_Remarks'                             => $request->payload['followUp_Prenatal_Remarks'] ?? ($OBGYNHistory->followUp_Prenatal_Remarks ?? null),
                'updatedby'                                             => Auth()->user()->idnumber,
                'updated_at'                                            => Carbon::now(),
            ];

            $patientPregnancyHistoryData = [
                'pregnancyNumber'   => null,
                'outcome'           => $request->payload['outcome'] ?? ($pregnancyHistory->outcome ?? null),
                'deliveryDate'      => $request->payload['deliveryDate'] ?? ($pregnancyHistory->deliveryDate ?? null),
                'complications'     => $request->payload['complications'] ?? ($pregnancyHistory->complications ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditionsData = [
                'conditionName'     => $request->payload['conditionName'] ?? ($gynecologicalConditions->conditionName ?? null),
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? ($gynecologicalConditions->diagnosisDate ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientMedicationsData = [
                'item_Id'               => $request->payload['item_Id'] ?? ($medications->item_Id ?? null),
                'drug_Description'      => $request->payload['drug_Description'] ?? ($medications->drug_Description ?? null),
                'dosage'                => $request->payload['dosage'] ?? ($medications->dosage ?? null),
                'reason_For_Use'        => $request->payload['reason_For_Use'] ?? ($medications->reason_For_Use ?? null),
                'adverse_Side_Effect'   => $request->payload['adverse_Side_Effect'] ?? ($medications->adverse_Side_Effect ?? null),
                'hospital'              => $request->payload['hospital'] ?? ($medications->hospital ?? null),
                'isPrescribed'          => $request->payload['isPrescribed'] ?? ($medications->isPrescribed ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPrivilegedCard = [
                'card_number'           => $request->payload['card_number'] ?? ($privilegedCard->card_number ?? null),
                'card_Type_Id'          => $request->payload['card_Type_Id'] ?? ($privilegedCard->card_Type_Id ?? null),
                'card_BenefitLevel'     => $request->payload['card_BenefitLevel'] ?? ($privilegedCard->card_BenefitLevel ?? null),
                'card_PIN'              => $request->payload['card_PIN'] ?? ($privilegedCard->card_PIN ?? null),
                'card_Bardcode'         => $request->payload['card_Bardcode'] ?? ($privilegedCard->card_Bardcode ?? null),
                'card_RFID'             => $request->payload['card_RFID'] ?? ($privilegedCard->card_RFID ?? null),
                'card_Balance'          => $request->payload['card_Balance'] ?? ($privilegedCard->card_Balance ?? null),
                'issued_Date'           => $request->payload['issued_Date'] ?? ($privilegedCard->issued_Date ?? null),
                'expiry_Date'           => $request->payload['expiry_Date'] ?? ($privilegedCard->expiry_Date ?? null),
                'points_Earned'         => $request->payload['points_Earned'] ?? ($privilegedCard->points_Earned ?? null),
                'points_Transferred'    => $request->payload['points_Transferred'] ?? ($privilegedCard->points_Transferred ?? null),
                'points_Redeemed'       => $request->payload['points_Redeemed'] ?? ($privilegedCard->points_Redeemed ?? null),
                'points_Forfeited'      => $request->payload['points_Forfeited'] ?? ($privilegedCard->points_Forfeited ?? null),
                'card_Status'           => $request->payload['card_Status'] ?? ($privilegedCard->card_Status ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientPrivilegedPointTransfers = [
                'toCard_Id'         => $request->payload['toCard_Id'] ?? ($privilegedPointTransfers->toCard_Id ?? null),
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? ($privilegedPointTransfers->description ?? null),
                'points'            => $request->payload['points'] ?? ($privilegedPointTransfers->points ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ];

            $patientPrivilegedPointTransactions = [
                'card_Id'           => $request->payload['card_Id'] ?? ($privilegedPointTransactions->card_Id ?? null),
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? ($privilegedPointTransactions->transaction_Type ?? null),
                'description'       => $request->payload['description'] ?? ($privilegedPointTransactions->description ?? null),
                'points'            => $request->payload['points'] ?? ($privilegedPointTransactions->points ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ]; 

            $patientDischargeInstructions = [
                'branch_Id'                         => $request->payload['branch_Id'] ?? ($dischargeInstructions->branch_Id ?? null),
                'general_Instructions'              => $request->payload['general_Instructions'] ?? ($dischargeInstructions->general_Instructions ?? null),
                'dietary_Instructions'              => $request->payload['dietary_Instructions'] ?? ($dischargeInstructions->dietary_Instructions ?? null),
                'medications_Instructions'          => $request->payload['medications_Instructions'] ?? ($dischargeInstructions->medications_Instructions ?? null),
                'activity_Restriction'              => $request->payload['activity_Restriction'] ?? ($dischargeInstructions->activity_Restriction ?? null),
                'dietary_Restriction'               => $request->payload['dietary_Restriction'] ?? ($dischargeInstructions->dietary_Restriction ?? null),
                'addtional_Notes'                   => $request->payload['addtional_Notes'] ?? ($dischargeInstructions->addtional_Notes ?? null),
                'clinicalPharmacist_OnDuty'         => $request->payload['clinicalPharmacist_OnDuty'] ?? ($dischargeInstructions->clinicalPharmacist_OnDuty ?? null),
                'clinicalPharmacist_CheckTime'      => $request->payload['clinicalPharmacist_CheckTime'] ?? ($dischargeInstructions->clinicalPharmacist_CheckTime ?? null),
                'nurse_OnDuty'                      => $request->payload['nurse_OnDuty'] ?? ($dischargeInstructions->nurse_OnDuty ?? null),
                'intructedBy_clinicalPharmacist'    => $request->payload['intructedBy_clinicalPharmacist'] ?? ($dischargeInstructions->intructedBy_clinicalPharmacist ?? null),
                'intructedBy_Dietitians'            => $request->payload['intructedBy_Dietitians'] ?? ($dischargeInstructions->intructedBy_Dietitians ?? null),
                'intructedBy_Nurse'                 => $request->payload['intructedBy_Nurse'] ?? ($dischargeInstructions->intructedBy_Nurse ?? null),
                'updatedby'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now()
            ];

            $patientDischargeMedications = [
                'instruction_Id'        => $request->payload['instruction_Id'] ?? ($dischargeMedications->instruction_Id ?? null),
                'Item_Id'               => $request->payload['Item_Id'] ?? ($dischargeMedications->Item_Id ?? null),
                'medication_Name'       => $request->payload['medication_Name'] ?? ($dischargeMedications->medication_Name ?? null),
                'medication_Type'       => $request->payload['medication_Type'] ?? ($dischargeMedications->medication_Type ?? null),
                'dosage'                => $request->payload['dosage'] ?? ($dischargeMedications->dosage ?? null),
                'frequency'             => $request->payload['frequency'] ?? ($dischargeMedications->frequency ?? null),
                'purpose'               => $request->payload['purpose'] ?? ($dischargeMedications->purpose ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpTreatment = [
                'instruction_Id'        => $request->payload['instruction_Id'] ?? ($dischargeFollowUpTreatment->instruction_Id ?? null),
                'treatment_Description' => $request->payload['treatment_Description'] ?? ($dischargeFollowUpTreatment->treatment_Description ?? null),
                'treatment_Date'        => $request->payload['treatment_Date'] ?? ($dischargeFollowUpTreatment->treatment_Date ?? null),
                'doctor_Id'             => $request->payload['doctor_Id'] ?? ($dischargeFollowUpTreatment->doctor_Id ?? null),
                'doctor_Name'           => $request->payload['doctor_Name'] ?? ($dischargeFollowUpTreatment->doctor_Name ?? null),
                'notes'                 => $request->payload['notes'] ?? ($dischargeFollowUpTreatment->notes ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpLaboratories = [
                'instruction_Id'    => $request->payload['instruction_Id'] ?? ($dischargeFollowUpLaboratories->instruction_Id ?? null),
                'item_Id'           => $request->payload['item_Id'] ?? ($dischargeFollowUpLaboratories->item_Id ?? null),
                'test_Name'         => $request->payload['test_Name'] ?? ($dischargeFollowUpLaboratories->test_Name ?? null),
                'test_DateTime'     => $request->payload['test_DateTime'] ?? ($dischargeFollowUpLaboratories->test_DateTime ?? null),
                'notes'             => $request->payload['notes'] ?? ($dischargeFollowUpLaboratories->notes ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ];

            $patientDischargeDoctorsFollowUp = [
                'instruction_Id'        => $request->payload['instruction_Id'] ?? ($dischargeDoctorsFollowUp->instruction_Id ?? null),
                'doctor_Id'             => $request->payload['doctor_Id'] ?? ($dischargeDoctorsFollowUp->doctor_Id ?? null),
                'doctor_Name'           => $request->payload['doctor_Name'] ?? ($dischargeDoctorsFollowUp->doctor_Name ?? null),
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? ($dischargeDoctorsFollowUp->doctor_Specialization ?? null),
                'schedule_Date'         => $request->payload['schedule_Date'] ?? ($dischargeDoctorsFollowUp->schedule_Date ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientHistoryData = [
                'branch_Id'                                 => $request->payload['branch_Id'] ?? ($patientHistory->branch_Id ?? null),
                'brief_History'                             => $request->payload['brief_History'] ?? ($patientHistory->brief_History ?? null),
                'pastMedical_History'                       => $request->payload['pastMedical_History'] ?? ($patientHistory->pastMedical_History ?? null),
                'family_History'                            => $request->payload['family_History'] ?? ($patientHistory->family_History ?? null),
                'personalSocial_History'                    => $request->payload['personalSocial_History'] ?? ($patientHistory->personalSocial_History ?? null),
                'chief_Complaint_Description'               => $request->payload['chief_Complaint_Description'] ?? ($patientHistory->chief_Complaint_Description ?? null),
                'impression'                                => $request->payload['impression'] ?? ($patientHistory->impression ?? null),
                'admitting_Diagnosis'                       => $request->payload['admitting_Diagnosis'] ?? ($patientHistory->admitting_Diagnosis ?? null),
                'discharge_Diagnosis'                       => $request->payload['discharge_Diagnosis'] ?? ($patientHistory->discharge_Diagnosis ?? null),
                'preOperative_Diagnosis'                    => $request->payload['preOperative_Diagnosis'] ?? ($patientHistory->preOperative_Diagnosis ?? null),
                'postOperative_Diagnosis'                   => $request->payload['postOperative_Diagnosis'] ?? ($patientHistory->postOperative_Diagnosis ?? null),
                'surgical_Procedure'                        => $request->payload['surgical_Procedure'] ?? ($patientHistory->surgical_Procedure ?? null),
                'physicalExamination_Skin'                  => $request->payload['physicalExamination_Skin'] ?? ($patientHistory->physicalExamination_Skin ?? null),
                'physicalExamination_HeadEyesEarsNeck'      => $request->payload['physicalExamination_HeadEyesEarsNeck'] ?? ($patientHistory->physicalExamination_HeadEyesEarsNeck ?? null),
                'physicalExamination_Neck'                  => $request->payload['physicalExamination_Neck'] ?? ($patientHistory->physicalExamination_Neck ?? null),
                'physicalExamination_ChestLungs'            => $request->payload['physicalExamination_ChestLungs'] ?? ($patientHistory->physicalExamination_ChestLungs ?? null),
                'physicalExamination_CardioVascularSystem'  => $request->payload['physicalExamination_CardioVascularSystem'] ?? ($patientHistory->physicalExamination_CardioVascularSystem ?? null),
                'physicalExamination_Abdomen'               => $request->payload['physicalExamination_Abdomen'] ?? ($patientHistory->physicalExamination_Abdomen ?? null),
                'physicalExamination_GenitourinaryTract'    => $request->payload['physicalExamination_GenitourinaryTract'] ?? ($patientHistory->physicalExamination_GenitourinaryTract ?? null),
                'physicalExamination_Rectal'                => $request->payload['physicalExamination_Rectal'] ?? ($patientHistory->physicalExamination_Rectal ?? null),
                'physicalExamination_Musculoskeletal'       => $request->payload['physicalExamination_Musculoskeletal'] ?? ($patientHistory->physicalExamination_Musculoskeletal ?? null),
                'physicalExamination_LympNodes'             => $request->payload['physicalExamination_LympNodes'] ?? ($patientHistory->physicalExamination_LympNodes ?? null),
                'physicalExamination_Extremities'           => $request->payload['physicalExamination_Extremities'] ?? ($patientHistory->physicalExamination_Extremities ?? null),
                'physicalExamination_Neurological'          => $request->payload['physicalExamination_Neurological'] ?? ($patientHistory->physicalExamination_Neurological ?? null),
                'updatedby'                                 => Auth()->user()->idnumber,
                'updated_at'                                => Carbon::now()
            ];

            $patientMedicalProcedureData = [
                'description'                   => $request->payload['description'] ?? ($patientMedicalProcedure->description ?? null),
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? ($patientMedicalProcedure->date_Of_Procedure ?? null),
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? ($patientMedicalProcedure->performing_Doctor_Id ?? null),
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? ($patientMedicalProcedure->performing_Doctor_Fullname ?? null),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now()
            ];

            $patientVitalSignsData = [
                'branch_Id'                 => 1,
                'transDate'                 => $today,
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  ($patientVitalSign->bloodPressureSystolic ?? null),
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] :  ($patientVitalSign->bloodPressureDiastolic ?? null),
                'temperature'               => isset($request->payload['temperature']) ? (int)$request->payload['temperature'] :  ($patientVitalSign->temperature ?? null),
                'pulseRate'                 => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] :  ($patientVitalSign->pulseRate ?? null),
                'respiratoryRate'           => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] :  ($patientVitalSign->respiratoryRate ?? null),
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (int)$request->payload['oxygenSaturation'] :  ($patientVitalSign->oxygenSaturation ?? null),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => $today
            ];

            $patientRegistryData = [
                'branch_Id'                     => $request->payload['branch_Id'] ?? ($patientRegistry->branch_Id ?? null),    
                'register_Source'               => $request->payload['register_Source'] ?? ($patientRegistry->register_Source ?? null),
                'register_Casetype'             => $request->payload['register_Casetype'] ?? ($patientRegistry->register_Casetype ?? null),
                'patient_Age'                   => $request->payload['age'] ?? ($patientRegistry->patient_Age ?? null),
                'mscAccount_Type'               => $request->payload['mscAccount_type'] ?? ($patientRegistry->mscAccount_Type ?? null),
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? ($patientRegistry->mscAccount_Discount_Id ?? null),
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_Trans_Types'] ?? ($patientRegistry->mscAccount_Trans_Types ?? null),  
                'mscPatient_Category'           => $request->payload['mscPatient_category'] ?? ($patientRegistry->mscPatient_Category ?? null),
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? ($patientRegistry->mscPrice_Groups ?? null),
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? ($patientRegistry->mscPrice_Schemes ?? null),
                'mscService_Type'               => $request->payload['mscService_Type'] ?? ($patientRegistry->mscService_Type ?? null),
                'queue_Number'                  => $request->payload['queue_number'] ?? ($patientRegistry->queue_Number ?? null),
                'arrived_Date'                  => $request->payload['arrived_date'] ?? ($patientRegistry->arrived_Date ?? null),
                'registry_Userid'               => Auth()->user()->idnumber,
                'registry_Date'                 => $today,
                'registry_Status'               => $request->payload['registry_Status'] ?? ($patientRegistry->registry_Status ?? null),
                'discharged_Userid'             => $request->payload['discharged_Userid'] ?? ($patientRegistry->discharged_Userid ?? null),
                'discharged_Date'               => $request->payload['discharged_Date'] ?? ($patientRegistry->discharged_Date ?? null),
                'billed_Userid'                 => $request->payload['billed_Userid'] ?? ($patientRegistry->billed_Userid ?? null),
                'billed_Date'                   => $request->payload['billed_Date'] ?? ($patientRegistry->billed_Date ?? null),
                'mscBroughtBy_Relationship_Id'  => $request->payload['mscBroughtBy_Relationship_Id'] ?? ($patientRegistry->mscBroughtBy_Relationship_Id ?? null),
                'mscCase_Indicators_Id'         => $request->payload['mscCase_Indicators_Id'] ?? ($patientRegistry->mscCase_Indicators_Id ?? null),
                'billed_Remarks'                => $request->payload['billed_Remarks'] ?? ($patientRegistry->billed_Remarks ?? null),
                'mgh_Userid'                    => $request->payload['mgh_Userid'] ?? ($patientRegistry->mgh_Userid ?? null),
                'mgh_Datetime'                  => $request->payload['mgh_Datetime'] ?? ($patientRegistry->mgh_Datetime ?? null),
                'untag_Mgh_Userid'              => $request->payload['untag_Mgh_Userid'] ?? ($patientRegistry->untag_Mgh_Userid ?? null),
                'untag_Mgh_Datetime'            => $request->payload['untag_Mgh_Datetime'] ?? ($patientRegistry->untag_Mgh_Datetime ?? null),
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? ($patientRegistry->isHoldReg ?? null),
                'hold_Userid'                   => $request->payload['hold_Userid'] ?? ($patientRegistry->hold_Userid ?? null),
                'hold_No'                       => $request->payload['hold_No'] ?? ($patientRegistry->hold_No ?? null),
                'hold_Date'                     => $request->payload['hold_Date'] ?? ($patientRegistry->hold_Date ?? null),
                'hold_Remarks'                  => $request->payload['hold_Remarks'] ?? ($patientRegistry->hold_Remarks ?? null),
                'isRevoked'                     => $request->payload['isRevoked'] ?? ($patientRegistry->isRevoked ?? null),
                'revokedBy'                     => $request->payload['revokedBy'] ?? ($patientRegistry->revokedBy ?? null),
                'revoked_Date'                  => $request->payload['revoked_Date'] ?? ($patientRegistry->revoked_Date ?? null),
                'revoked_Remarks'               => $request->payload['revoked_Remarks'] ?? ($patientRegistry->revoked_Remarks ?? null),
                'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? ($patientRegistry->guarantor_Id ?? null),
                'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? ($patientRegistry->guarantor_Name ?? null),
                'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? ($patientRegistry->guarantor_Approval_code ?? null),
                'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? ($patientRegistry->guarantor_Approval_no ?? null),
                'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? ($patientRegistry->guarantor_Approval_date ?? null),
                'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? ($patientRegistry->guarantor_Validity_date ?? null),
                'guarantor_Approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? ($patientRegistry->guarantor_Approval_remarks ?? null),
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false) ?? ($patientRegistry->isWithCreditLimit ?? null),
                'guarantor_Credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? ($patientRegistry->guarantor_Credit_Limit ?? null),
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? ($patientRegistry->isWithPhilHealth ?? null),
                'philhealth_Number'             => $request->payload['philhealth_Number'] ?? ($patientRegistry->philhealth_Number ?? null),
                'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? ($patientRegistry->isWithMedicalPackage ?? null),
                'medical_Package_Id'            => $request->payload['Medical_Package_id'] ?? ($patientRegistry->medical_Package_Id ?? null),
                'medical_Package_Name'          => $request->payload['medical_Package_Name'] ?? ($patientRegistry->medical_Package_Name ?? null),
                'medical_Package_Amount'        => $request->payload['medical_Package_Amount'] ?? ($patientRegistry->medical_Package_Amount ?? null),
                // 'chief_Complaint_Description'   => $request->payload['clinical_chief_complaint'] ?? $patientRegistry->chief_Complaint_Description,
                'impression'                    => $request->payload['impression'] ?? ($patientRegistry->impression ?? null),
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? ($patientRegistry->isCriticallyIll ?? null),
                'illness_Type'                  => $request->payload['illness_Type'] ?? ($patientRegistry->illness_Type ?? null),
                'isHemodialysis'                => $isHemodialysis ?? ($patientRegistry->isHemodialysis ?? null),
                'isPeritoneal'                  => $isPeritoneal ?? ($patientRegistry->isPeritoneal ?? null),
                'isLINAC'                       => $isLINAC ?? ($patientRegistry->isLINAC ?? null),
                'isCOBALT'                      => $isCOBALT ?? ($patientRegistry->isCOBALT ?? null),
                'isBloodTrans'                  => $isBloodTrans ?? ($patientRegistry->isBloodTrans ?? null),
                'isChemotherapy'                => $isChemotherapy ?? ($patientRegistry->isChemotherapy ?? null),
                'isBrachytherapy'               => $isBrachytherapy ?? ($patientRegistry->isBrachytherapy ?? null),
                'isDebridement'                 => $isDebridement ?? ($patientRegistry->isDebridement ?? null),
                'isTBDots'                      => $isTBDots ?? ($patientRegistry->isTBDots ?? null),
                'isPAD'                         => $isPAD ?? ($patientRegistry->isPAD ?? null),
                'isRadioTherapy'                => $isRadioTherapy ?? ($patientRegistry->isRadioTherapy ?? null),
                'attending_Doctor'              => $request->payload['selectedConsultant'][0]['attending_Doctor'] ?? ($patientRegistry->attending_Doctor ?? null),
                'attending_Doctor_fullname'     => $request->payload['selectedConsultant'][0]['attending_Doctor_fullname'] ?? ($patientRegistry->attending_Doctor_fullname ?? null),
                'bmi'                           => isset($request->payload['bmi']) ? (float)$request->payload['bmi'] : ($patientRegistry->bmi ?? null),
                'weight'                        => isset($request->payload['weight']) ? (float)$request->payload['weight'] : ($patientRegistry->weight ?? null),
                'weightUnit'                    => $request->payload['weightUnit'] ?? ($patientRegistry->weightUnit ?? null),
                'height'                        => isset($request->payload['height']) ? (float)$request->payload['height'] : ($patientRegistry->height ?? null),
                'heightUnit'                    => $request->payload['height_Unit'] ?? ($patientRegistry->heightUnit ?? null),
                'bloodPressureSystolic'         => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] : ($patientRegistry->bloodPressureSystolic ?? null),
                'bloodPressureDiastolic'        => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : ($patientRegistry->bloodPressureDiastolic ?? null),
                'pulseRate'                     => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : ($patientRegistry->pulseRate ?? null),
                'respiratoryRate'               => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : ($patientRegistry->respiratoryRate ?? null),
                'oxygenSaturation'              => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : ($patientRegistry->oxygenSaturation ?? null),
                'isOpenLateCharges'             => $request->payload['LateCharges'] ?? ($patientRegistry->isOpenLateCharges ?? null),
                'mscCase_Result_Id'             => $request->payload['mscCase_result_id'] ?? ($patientRegistry->mscCase_Result_Id ?? null),
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? ($patientRegistry->isAutopsy ?? null),
                'barcode_Image'                 => $request->payload['barcode_Image'] ?? ($patientRegistry->barcode_Image ?? null),
                'barcode_Code_Id'               => $request->payload['barcode_Code_Id'] ?? ($patientRegistry->barcode_Code_Id ?? null),
                'barcode_Code_String'           => $request->payload['barcode_Code_String'] ?? ($patientRegistry->barcode_Code_String ?? null),
                'isWithConsent_DPA'              => $request->payload['isWithConsent_DPA'] ?? ($patientRegistry->isWithConsent_DPA ?? null),
                'registry_Remarks'              => $request->payload['registry_Remarks'] ?? ($patientRegistry->registry_Remarks ?? null), 
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => $today
            ];   

            $patientImmunizationsData = [
                'branch_id'             => 1,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? ($patientImmunization->vaccine_Id ?? null),
                'administration_Date'   => $request->payload['administration_Date'] ?? ($patientImmunization->administration_Date ?? null),
                'dose'                  => $request->payload['dose'] ?? ($patientImmunization->dose ?? null),
                'site'                  => $request->payload['site'] ?? ($patientImmunization->site ?? null),
                'administrator_Name'    => $request->payload['administrator_Name'] ?? ($patientImmunization->administrator_Name ?? null),
                'Notes'                 => $request->payload['Notes'] ?? ($patientImmunization->Notes ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => $today
            ];

            $patientAdministeredMedicineData = [
                'item_Id'               => $request->payload['item_Id'] ?? ($patientAdministeredMedicine->item_Id ?? null),
                'quantity'              => $request->payload['quantity'] ?? ($patientAdministeredMedicine->quantity ?? null),
                'administered_Date'     => $request->payload['administered_Date'] ?? ($patientAdministeredMedicine->administered_Date ?? null),
                'administered_By'       => $request->payload['administered_By'] ?? ($patientAdministeredMedicine->administered_By ?? null),
                'reference_num'         => $request->payload['reference_num'] ?? ($patientAdministeredMedicine->reference_num ?? null),
                'transaction_num'       => $request->payload['transaction_num'] ?? ($patientAdministeredMedicine->transaction_num ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => $today,
            ]; 

            $allergyResults = [
                'patientAllergyData' => [],
                'patientCauseAllergyData' => [],
                'patientSymptomsOfAllergy' => [],
                'patientDrugUsedForAllergyData' => [],
            ];
            
            $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier);

            if (isset($request->payload['selectedAllergy']) && is_array($request->payload['selectedAllergy'])) {
                foreach ($request->payload['selectedAllergy'] as $item) {
                    $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category = null, $item);
                    
                    $allergyResults['patientAllergyData'][] = $registerData['patientAllergyData'];
                    $allergyResults['patientCauseAllergyData'][] = $registerData['patientCauseAllergyData'];
                    $allergyResults['patientDrugUsedForAllergyData'][] = $registerData['patientDrugUsedForAllergyData'];

                    if (isset($item['symptoms']) && is_array($item['symptoms'])) {
                        foreach ($item['symptoms'] as $symptom) {
                                $registerData = $this->getRegisterPatientData($request, $patient_id, $registry_id, $patientIdentifier, $patient_category = null, $item, $symptom);
                                $allergyResults['patientSymptomsOfAllergy'][] = $registerData['patientSymptomsOfAllergy'];
                        }
                    }
                }
            }
            
            if ($isPatientRegistered) { 
                $patient->update($patientData);
                $pastImmunization->update($patientPastImmunizationData);
                $pastMedicalHistory->update($patientPastMedicalHistoryData);
                $pastMedicalProcedure->update($patientPastMedicalProcedureData);
                $pastBadHabits->update($patientPastBadHabitsData);
                $privilegedCard->update($patientPrivilegedCard);
                $privilegedPointTransfers->update($patientPrivilegedPointTransfers);
                $privilegedPointTransactions->update($patientPrivilegedPointTransactions);
                
                $patientRegistry->update($patientRegistryData);

                $deleteAllergy = $patientRegistry->allergies()
                    ->where('patient_Id', $patientRegistry->patient_Id)
                    ->where('case_No', $patientRegistry->case_No)
                    ->whereDate('created_at', $today)
                    ->update(['isDeleted' => 1]);
                
                if ($deleteAllergy) {
                    $deletedAllergyID = $patientRegistry->allergies()
                        ->where('patient_Id', $patientRegistry->patient_Id)
                        ->where('case_No', $patientRegistry->case_No)
                        ->where('isDeleted', 1)
                        ->whereDate('created_at', $today)
                        ->pluck('id');
                    foreach ($deletedAllergyID as $allergyID) {
                        $allergies = $patientRegistry->allergies()->find($allergyID);
                        if ($allergies) {
                            $allergies->cause_of_allergy()
                                ->where('assessID', $allergyID)
                                ->update(['isDeleted' => 1]);
                            $allergies->symptoms_allergy()
                                ->where('assessID', $allergyID)
                                ->update(['isDeleted' => 1]);
                            $allergies->drug_used_for_allergy()
                                ->where('assessID', $allergyID)
                                ->update(['isDeleted' => 1]);
                        }
                    }
                }
            
                $allergies = $patientRegistry->allergies()->createMany($allergyResults['patientAllergyData']);
                $arrayCause = [];
                $arraySymptoms = [];
                $arrayDrugs = [];
                foreach ($allergies as $allergy) {
                    $assessID = $allergy->id;
                    if (!empty($allergyResults['patientCauseAllergyData'])) {
                        $cause = array_shift($allergyResults['patientCauseAllergyData']);
                        $cause['assessID'] = $assessID; 
                        $arrayCause[] = $cause; 
                    }
                    if (!empty($allergyResults['patientDrugUsedForAllergyData'])) {
                        $drug = array_shift($allergyResults['patientDrugUsedForAllergyData']);
                        $drug['assessID'] = $assessID; 
                        $arrayDrugs[] = $drug; 
                    }
                    if (!empty($allergyResults['patientSymptomsOfAllergy'])) {
                        foreach ($allergyResults['patientSymptomsOfAllergy'] as $symptom) {
                            if ($symptom['allergy_Type_Id'] == $cause['allergy_Type_Id']) { 
                                $symptom['assessID'] = $assessID; 
                                $arraySymptoms[] = $symptom; 
                            }
                        }
                    }
                }
                
                $uniqueCauses = [];
                $uniqueDrugs = [];
                $uniqueCauses = $this->getUniqueAllergy($arrayCause);
                $uniqueDrugs = $this->getUniqueAllergy($arrayDrugs);

                if (!empty($uniqueCauses)) {
                    $allergy->cause_of_allergy()->insert($uniqueCauses);
                }
                if (!empty($arraySymptoms)) {
                    $allergy->symptoms_allergy()->insert(array_values($arraySymptoms));
                }
                if (!empty($uniqueDrugs)) {
                    $allergy->drug_used_for_allergy()->insert($uniqueDrugs);
                }

                $patientHistory->update($patientHistoryData);
                $patientMedicalProcedure->update($patientMedicalProcedureData);
                $patientVitalSign->update($patientVitalSignsData);
                $patientImmunization->update($patientImmunizationsData);
                $patientAdministeredMedicine->update($patientAdministeredMedicineData);
                $OBGYNHistory->update($patientOBGYNHistoryData);
                $pregnancyHistory->update($patientPregnancyHistoryData);
                $gynecologicalConditions->update($patientGynecologicalConditionsData);
                $badHabits->update($patientBadHabitsData);
                $patientDoctors->update($patientDoctorsData);
                $physicalAbdomen->update($patientPhysicalAbdomenData);
                $pertinentSignAndSymptoms->update($patientPertinentSignAndSymptomsData);
                $physicalExamtionChestLungs->update($patientPhysicalExamtionChestLungsData);
                $courseInTheWard->update($patientCourseInTheWardData);
                $physicalExamtionCVS->update($patientPhysicalExamtionCVSData);
                $physicalExamtionGeneralSurvey->update($patientPhysicalExamtionGeneralSurveyData);
                $physicalExamtionHEENT->update($patientPhysicalExamtionHEENTData);
                $physicalGUIE->update($patientPhysicalGUIEData);
                $physicalNeuroExam->update($patientPhysicalNeuroExamData);
                $physicalSkinExtremities->update($patientPhysicalSkinExtremitiesData);
                $medications->update($patientMedicationsData);
                $dischargeMedications->update($patientDischargeMedications);
                $dischargeFollowUpTreatment->update($patientDischargeFollowUpTreatment);
                $dischargeFollowUpLaboratories->update($patientDischargeFollowUpLaboratories);
                $dischargeDoctorsFollowUp->update($patientDischargeDoctorsFollowUp);
            } else {
                echo 'Patient is not registered';
                $patient->past_medical_procedures()->create($registerData['patientPastMedicalProcedureData']);
                $patient->past_medical_history()->create($registerData['patientPastMedicalHistoryData']);
                $patient->past_immunization()->create($registerData['patientPastImmunizationData']);
                $patient->past_bad_habits()->create($registerData['patientPastBadHabitsData']);

                $newPrivelgedCard = $patient->privilegedCard()->create($registerData['patientPrivilegedCard']);
                $registerData['patientPrivilegedPointTransfers']['fromCard_Id'] = $newPrivelgedCard->id;
                $registerData['patientPrivilegedPointTransfers']['toCard_Id'] = $newPrivelgedCard->id;
                $registerData['patientPrivilegedPointTransactions']['card_Id'] = $newPrivelgedCard->id;
                $newPrivelgedCard->pointTransfers()->create($registerData['patientPrivilegedPointTransfers']);
                $newPrivelgedCard->pointTransactions()->create($registerData['patientPrivilegedPointTransactions']);

                $patientRegistry = $patient->patientRegistry()->create($registerData['patientRegistryData']);

                $allergies = $patientRegistry->allergies()->createMany($allergyResults['patientAllergyData']);
                $arrayCause = [];
                $arraySymptoms = [];
                $arrayDrugs = [];
                foreach ($allergies as $allergy) {
                    $assessID = $allergy->id;
                    if (!empty($allergyResults['patientCauseAllergyData'])) {
                        $cause = array_shift($allergyResults['patientCauseAllergyData']);
                        $cause['assessID'] = $assessID; 
                        $arrayCause[] = $cause; 
                    }
                    if (!empty($allergyResults['patientDrugUsedForAllergyData'])) {
                        $drug = array_shift($allergyResults['patientDrugUsedForAllergyData']);
                        $drug['assessID'] = $assessID; 
                        $arrayDrugs[] = $drug; 
                    }
                    if (!empty($allergyResults['patientSymptomsOfAllergy'])) {
                        foreach ($allergyResults['patientSymptomsOfAllergy'] as $symptom) {
                            if ($symptom['allergy_Type_Id'] == $cause['allergy_Type_Id']) { 
                                $symptom['assessID'] = $assessID; 
                                $arraySymptoms[] = $symptom; 
                            }
                        }
                    }
                }
                
                $uniqueCauses = [];
                $uniqueDrugs = [];
                $uniqueCauses = $this->getUniqueAllergy($arrayCause);
                $uniqueDrugs = $this->getUniqueAllergy($arrayDrugs);

                if (!empty($uniqueCauses)) {
                    $allergy->cause_of_allergy()->insert($uniqueCauses);
                }
                if (!empty($arraySymptoms)) {
                    $allergy->symptoms_allergy()->insert(array_values($arraySymptoms));
                }
                if (!empty($uniqueDrugs)) {
                    $allergy->drug_used_for_allergy()->insert($uniqueDrugs);
                }

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

                $newOBGYNHistory = $patientRegistry->oBGYNHistory()->create($registerData['patientOBGYNHistory']);
                $registerData['patientPregnancyHistoryData']['OBGYNHistoryID'] = $newOBGYNHistory->id;
                $registerData['patientGynecologicalConditions']['OBGYNHistoryID'] = $newOBGYNHistory->id;
                $newOBGYNHistory->PatientPregnancyHistory()->create($registerData['patientPregnancyHistoryData']);
                $newOBGYNHistory->gynecologicalConditions()->create($registerData['patientGynecologicalConditions']);

                $newDischargeInstructions = $patient->dischargeInstructions()->create($registerData['patientDischargeInstructions']);
                $registerData['patientDischargeMedications']['instruction_Id'] = $newDischargeInstructions->id;
                $registerData['patientDischargeFollowUpLaboratories']['instruction_Id'] = $newDischargeInstructions->id;
                $registerData['patientDischargeFollowUpTreatment']['instruction_Id'] = $newDischargeInstructions->id;
                $registerData['patientDischargeDoctorsFollowUp']['instruction_Id'] = $newDischargeInstructions->id;
                $newDischargeInstructions->dischargeMedications()->create($registerData['patientDischargeMedications']);
                $newDischargeInstructions->dischargeFollowUpTreatment()->create($registerData['patientDischargeFollowUpTreatment']);
                $newDischargeInstructions->dischargeFollowUpLaboratories()->create($registerData['patientDischargeFollowUpLaboratories']);
                $newDischargeInstructions->dischargeDoctorsFollowUp()->create($registerData['patientDischargeDoctorsFollowUp']);
            }

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_patient_data')->commit();
            DB::connection('sqlsrv')->commit();

            return response()->json([
                'message' => "Patient data updated successfully",
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            DB::connection('sqlsrv')->rollBack(); 

            return response()->json([
                'message' => 'Failed to update outpatient data',
                'error' => $e->getMessage(), $e->getTraceAsString()
            ], 500);
        }
    }

    public function getrevokedoutpatient() {
        try {
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $today = Carbon::now()->format('Y-m-d');

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_trans_types', 2);
                $query->where('isRevoked', 1);
                // $query->whereDate('revoked_date', '>=', $today);

                if(Request()->keyword) {
                    $query->where(function($subQuery) {
                        $subQuery->where('lastname', 'LIKE', '%'.Request()->keyword.'%')
                                ->orWhere('firstname', 'LIKE', '%'.Request()->keyword.'%')
                                ->orWhere('patient_id', 'LIKE', '%'.Request()->keyword.'%');
                    });
                }
            });
            $data->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Failed to get revoked outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revokepatient(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('patient_Id', $id)->first();

            $patientRegistry->update([
                'isRevoked' => 1,
                'revokedBy' => Auth()->user()->idnumber,
                'revoked_date' => Carbon::now(),
                'revoked_remarks' => $request->payload['revoked_remarks'] ?? null,
                'revoked_hostname' => (new GetIP())->getHostname(),
                'UpdatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);

            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient revoked successfully',
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to revoke patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unrevokepatient(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('patient_Id', $id)->first();

            $patientRegistry->update([
                'isRevoked' => 0,
                'revokedBy' => null,
                'revoked_date' => null,
                'revoked_remarks' => null,
                'revoked_hostname' => null,
                'UpdatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);

            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient revoked successfully',
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to revoke patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}