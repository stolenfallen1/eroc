<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientAppointmentsTemporary;
use App\Models\HIS\PatientDoctors;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientPastAllergyHistory;
use App\Models\HIS\PatientPastBadHabits;
use App\Models\HIS\PatientPastImmunizations;
use App\Models\HIS\PatientPastMedicalHistory;
use App\Models\HIS\PatientPastMedicalProcedures;
use App\Models\HIS\PatientPhysicalExamtionChestLungs;
use App\Models\HIS\PatientPhysicalExamtionHEENT;
use App\Models\HIS\PatientPhysicalNeuroExam;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Carbon\Carbon;
use DeviceDetector\Parser\Device\Console;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetIP;

class OutpatientRegistrationController extends Controller
{
    public function index() {
        try { 
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $today = Carbon::now()->format('Y-m-d');
            
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 2); 
                $query->where('isRevoked', 0);
                $query->whereDate('registry_Date', $today)
                    ->where(function($q) use ($today) {
                        $q->whereNull('discharged_Date')
                            ->orWhereDate('discharged_Date', '>=', $today);
                });

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

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get outpatient patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $today = Carbon::now();
            $sequence = SystemSequence::where('code','MPID')->where('branch_id', 1)->first();
            $registry_sequence = SystemSequence::where('code','MOPD')->where('branch_id', 1)->first();
            if (!$sequence || !$registry_sequence) {
                throw new \Exception('Sequence not found');
            }

            $registry_id        = $registry_sequence->seq_no;
            $patientIdentifier  = $request->payload['patientIdentifier'] ?? null;
            $isHemodialysis     = ($patientIdentifier === 1) ? true : false;
            $isPeritoneal       = ($patientIdentifier === 2) ? true : false;
            $isLINAC            = ($patientIdentifier === 3) ? true : false;
            $isCOBALT           = ($patientIdentifier === 4) ? true : false;
            $isBloodTrans       = ($patientIdentifier === 5) ? true : false;
            $isChemotherapy     = ($patientIdentifier === 6) ? true : false;
            $isBrachytherapy    = ($patientIdentifier === 7) ? true : false;
            $isDebridement      = ($patientIdentifier === 8) ? true : false;
            $isTBDots           = ($patientIdentifier === 9) ? true : false;
            $isPAD              = ($patientIdentifier === 10) ? true : false;
            $isRadioTherapy     = ($patientIdentifier === 11) ? true : false;

            $existingPatient = Patient::where('lastname', $request->payload['lastname'])->where('firstname', $request->payload['firstname'])->where('birthdate', $request->payload['birthdate'])->first();
            if ($existingPatient) {
                $patient_id = $existingPatient->patient_Id;
                $patient_category = 3;
            } else {
                $patient_category = 2;
                $patient_id = $sequence->seq_no;
                $sequence->update([
                    'seq_no' => $sequence->seq_no + 1,
                    'recent_generated' => $sequence->seq_no,
                ]);
            }

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
            ];

            $patientData = [
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
            ];

            $patientPastImmunizationData = [
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
            ];

            $patientPastMedicalHistoryData = [
                'branch_Id'                 => 1,    
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => '',
                'diagnosis_Date'            => '',
                'treament'                  => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(), 
            ];

            $patientPastMedicalProcedureData = [
                'patient_Id'                => $patient_id,
                'description'               => '',
                'date_Of_Procedure'         => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientPastAllergyHistoryData = [
                'patient_Id'                => $patient_id,
                'family_History'            => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientPastCauseOfAllergyData = [
                'history_Id'            => '',
                'allergy_Type_Id'       => '',
                'duration'              => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientPastSymptomsOfAllergyData = [
                'history_Id'            => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'patient_Id'        => $patient_id,
                'drug_Description'  => '',
                'hospital'          => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'patient_Id'                    => $patient_id,
                'description'                   => null,
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientPrivilegedCard = [
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
            ];

            $patientPrivilegedPointTransfers = [
                'fromCard_Id'       => '',
                'toCard_Id'         => $request->payload['toCard_Id'] ?? 4,
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];

            $patientPrivilegedPointTransactions = [
                'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ]; 

            $patientRegistryData = [
                'branch_Id'                     =>  1,
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'register_Source'               => $request->payload['register_Source'] ?? null,
                'register_Casetype'             => $request->payload['register_Casetype'] ?? null,
                'patient_Age'                   => $request->payload['age'] ?? null,
                'mscAccount_type'               => $request->payload['mscAccount_type'] ?? '',
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? null,
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_Trans_Types'] ?? 5, 
                'mscPatient_Category'           => $patient_category,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? null,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? 100,
                'mscService_Type'               => $request->payload['mscService_Type'] ?? null,
                'queue_number'                  => $request->payload['queue_number'] ?? null,
                'arrived_date'                  => $request->payload['arrived_date'] ?? null,
                'registry_Userid'               => Auth()->user()->idnumber,
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
                'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? null,
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
                // 'chief_Complaint_Description'   => $request->payload['chief_Complaint_Description'] ?? null,
                'impression'                    => $request->payload['impression'] ?? null,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? false,
                'illness_Type'                  => $request->payload['illness_Type'] ?? null,
                'isHemodialysis'                => $isHemodialysis,
                'isPeritoneal'                  => $isPeritoneal,
                'isLINAC'                       => $isLINAC,
                'isCOBALT'                      => $isCOBALT,
                'isBloodTrans'                  => $isBloodTrans,
                'isChemotherapy'                => $isChemotherapy,
                'isBrachytherapy'               => $isBrachytherapy,
                'isDebridement'                 => $isDebridement,
                'isTBDots'                      => $isTBDots,
                'isPAD'                         => $isPAD,
                'isRadioTherapy'                => $isRadioTherapy,
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
            ];

            $patientHistoryData = [
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
            ];

            $patientAdministeredMedicineData = [
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
            ];
            
            $patientAllergyData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'family_History'    => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientCauseAllergyData = [
                'allergies_Id'        => '',
                'allergy_Type_Id'   => '',
                'duration'          => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientSymptomsOfAllergy = [
                'allergies_Id'            => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientImmunizationsData = [
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
            ];

            $patientMedicalProcedureData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => null,
                'date_Of_Procedure'             => null,
                'performing_Doctor_Id'          => null,
                'performing_Doctor_Fullname'    => null,
                'createdby'                     => Auth()->user()->idnumber,
                'updatedby'                     => Auth()->user()->idnumber,
            ];

            $patientVitalSignsData = [
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
            ]; 

            $patientBadHabitsData = [
                'patient_Id' => $patient_id,
                'case_No'   => $registry_id,
                'description' => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientDoctorsData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => '',
                'doctors_Fullname'  => '',
                'role_Id'           => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientPhysicalAbdomenData = [
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
            ];

            $patientPertinentSignAndSymptomsData = [
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
            ];

            $patientPhysicalExamtionChestLungsData = [
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
            ];

            $patientCourseInTheWardData = [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                   => '',
                'createdby'                             => Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ];

            $patientPhysicalExamtionCVSData = [
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
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => '',
                'altered_Sensorium'     => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientPhysicalExamtionHEENTData = [
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
            ];

            $patientPhysicalGUIEData = [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'essentially_Normal'                => '',
                'blood_StainedIn_Exam_Finger'       => '',
                'cervical_Dilatation'               => '',
                'presence_Of_AbnormalDischarge'     => '',
                'others_Description'                => '',
                'createdby'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ];

            $patientPhysicalNeuroExamData = [
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
            ];

            $patientPhysicalSkinExtremitiesData = [
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
            ];

            $patientOBGYNHistory = [
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
            ];

            $patientPregnancyHistoryData = [
                'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => '',
                'deliveryDate'      => '',
                'complications'     => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditions = [
                'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientMedicationsData = [
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
                
            ];

            $patientDischargeInstructions = [
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
            ];

            $patientDischargeDoctorsFollowUp = [
                'instruction_Id'            => '',
                'doctor_Id'                 => '',
                'doctor_Name'               => '',
                'doctor_Specialization'     => '',
                'schedule_Date'             => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientDischargeFollowUpTreatment = [
                'instruction_Id'                => '',
                'treatment_Description'         => '',
                'treatment_Date'                => '',
                'doctor_Id'                     => '',
                'doctor_Name'                   => '',
                'notes'                         => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientDischargeMedications = [
                'instruction_Id'        => '',
                'item_Id'               => '',
                'medication_Name'       => '',
                'medication_Type'       => '',
                'dosage'                => '',
                'frequency'             => '',
                'purpose'               => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientDischargeFollowUpLaboratories = [
                'instruction_Id'        => '',
                'item_Id'               => '',
                'test_Name'             => '',
                'test_DateTime'         => '',
                'notes'                 => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patient = Patient::updateOrCreate($patientRule, $patientData);
            if (!$patient) {
                throw new \Exception('Failed to create patient master data');
            } else {
                $patient->past_medical_procedures()->create($patientPastMedicalProcedureData);
                $patient->past_medical_history()->create($patientPastMedicalHistoryData);
                $patient->past_immunization()->create($patientPastImmunizationData);
                $patient->past_bad_habits()->create($patientPastBadHabitsData);
                $patient->drug_used_for_allergy()->create($patientDrugUsedForAllergyData);

                $patientPriviledgeCard = $patient->privilegedCard()->create($patientPrivilegedCard);
                $patientPrivilegedPointTransfers['fromCard_Id'] = $patientPriviledgeCard->id;
                $patientPrivilegedPointTransfers['toCard_Id'] = $patientPriviledgeCard->id;
                $patientPrivilegedPointTransactions['card_Id'] = $patientPriviledgeCard->id;
                $patientPriviledgeCard->pointTransactions()->create($patientPrivilegedPointTransactions);
                $patientPriviledgeCard->pointTransfers()->create($patientPrivilegedPointTransfers);
    
                $pastHistory = $patient->past_allergy_history()->create($patientPastAllergyHistoryData);
                $patientPastCauseOfAllergyData['history_Id'] =   $pastHistory->id;
                $patientPastSymptomsOfAllergyData['history_Id'] =   $pastHistory->id;
                $pastHistory->pastCauseOfAllergy()->create($patientPastCauseOfAllergyData);
                $pastHistory->pastSymptomsOfAllergy()->create($patientPastSymptomsOfAllergyData);
            }

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingRegistry) {
                $patientRegistry = $patient->patientRegistry()->create($patientRegistryData);
                $patientRegistry->history()->create($patientHistoryData);
                $patientRegistry->immunizations()->create($patientImmunizationsData);
                $patientRegistry->vitals()->create($patientVitalSignsData);
                $patientRegistry->medical_procedures()->create($patientMedicalProcedureData);
                $patientRegistry->administered_medicines()->create($patientAdministeredMedicineData);
                $patientRegistry->bad_habits()->create($patientBadHabitsData);
                $patientRegistry->patientDoctors()->create($patientDoctorsData);
                $patientRegistry->pertinentSignAndSymptoms()->create($patientPertinentSignAndSymptomsData);
                $patientRegistry->physicalExamtionChestLungs()->create($patientPhysicalExamtionChestLungsData);
                $patientRegistry->courseInTheWard()->create($patientCourseInTheWardData);
                $patientRegistry->physicalExamtionCVS()->create($patientPhysicalExamtionCVSData);
                $patientRegistry->medications()->create($patientMedicationsData);
                $patientRegistry->physicalExamtionHEENT()->create($patientPhysicalExamtionHEENTData);
                $patientRegistry->physicalSkinExtremities()->create($patientPhysicalSkinExtremitiesData);
                $patientRegistry->physicalAbdomen()->create($patientPhysicalAbdomenData);
                $patientRegistry->physicalNeuroExam()->create($patientPhysicalNeuroExamData);
                $patientRegistry->physicalGUIE()->create($patientPhysicalGUIEData);
                $patientRegistry->PhysicalExamtionGeneralSurvey()->create($patientPhysicalExamtionGeneralSurveyData);

                $OBG = $patientRegistry->oBGYNHistory()->create($patientOBGYNHistory);
                $patientPregnancyHistoryData['OBGYNHistoryID'] = $OBG->id;
                $patientGynecologicalConditions['OBGYNHistoryID'] = $OBG->id;
                $OBG->PatientPregnancyHistory()->create($patientPregnancyHistoryData);
                $OBG->gynecologicalConditions()->create($patientGynecologicalConditions);

                $patientAllergy = $patientRegistry->allergies()->create($patientAllergyData);
                $last_inserted_id = $patientAllergy->id;
                $patientCauseAllergyData['allergies_Id'] = $last_inserted_id;
                $patientSymptomsOfAllergy['allergies_Id'] = $last_inserted_id;
                $patientAllergy->cause_of_allergy()->create($patientCauseAllergyData);
                $patientAllergy->symptoms_allergy()->create($patientSymptomsOfAllergy);

                $patientDischarge = $patientRegistry->dischargeInstructions()->create($patientDischargeInstructions);
                $patientDischargeMedications['instruction_Id'] = $patientDischarge->id;
                $patientDischargeFollowUpLaboratories['instruction_Id'] = $patientDischarge->id;
                $patientDischargeFollowUpTreatment['instruction_Id'] = $patientDischarge->id;
                $patientDischargeDoctorsFollowUp['instruction_Id'] = $patientDischarge->id;
                $patientDischarge->dischargeMedications($patientDischargeMedications)->create();
                $patientDischarge->dischargeFollowUpLaboratories()->create($patientDischargeFollowUpLaboratories);
                $patientDischarge->dischargeFollowUpTreatment()->create($patientDischargeFollowUpTreatment);
                $patientDischarge->dischargeDoctorsFollowUp()->create($patientDischargeDoctorsFollowUp);

            } else {
                throw new \Exception('Patient already registered today');
            }

            if (!$patient || !$patientRegistry) {
                throw new \Exception('Registration failed, rollback transaction');
            } else {
                $registry_sequence->update([
                    'seq_no' => $registry_sequence->seq_no + 1,
                    'recent_generated' => $registry_sequence->seq_no,
                ]);
                DB::connection('sqlsrv_patient_data')->commit();
                return response()->json([
                    'message' => 'Outpatient data registered successfully',
                    'patient' => $patient,
                    'patientRegistry' => $patientRegistry
                ], 200);
            }

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patient = Patient::findOrFail($id);
            $today = Carbon::now()->format('Y-m-d');
            $patient_id = $patient->patient_Id;
            $registry_sequence = SystemSequence::where('code','MOPD')->where('branch_id', 1)->first();

            $isPatientRegistered = $patient->patientRegistry()->whereDate('created_at', $today)->exists();
            if ($isPatientRegistered) {
                $registry_id = $patient->patientRegistry()->whereDate('created_at', $today)->first()->case_No;
            } else {
                if (!$registry_sequence) {
                    throw new \Exception('Sequence not found');
                } else {
                    $registry_id = $registry_sequence->seq_no;
                }
            }

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

            $pastImmunization                   = $patient->past_immunization()->first();
            $pastMedicalHistory                 = $patient->past_medical_history()->first();
            $pastMedicalProcedure               = $patient->past_medical_procedures()->first();
            $pastAllergyHistory                 = $patient->past_allergy_history()->first();  
            $pastCauseOfAllergy                 = $pastAllergyHistory->pastCauseOfAllergy()->first();
            $pastSymtomsOfAllergy               = $pastAllergyHistory->pastSymptomsOfAllergy()->first();
            $drugUsedForAllergy                 = $patient->drug_used_for_allergy()->first();
            $pastBadHabits                      = $patient->past_bad_habits()->first();
            $privilegedCard                     = $patient->privilegedCard()->first();
            $privilegedPointTransfers           = $privilegedCard->pointTransfers()->first();
            $privilegedPointTransactions        = $privilegedCard->pointTransactions()->first();
            $dischargeInstructions              = $patient->dischargeInstructions()->first();
            $dischargeMedications               = $dischargeInstructions->dischargeMedications()->first();
            $dischargeFollowUpTreatment         = $dischargeInstructions->dischargeFollowUpTreatment()->first();
            $dischargeFollowUpLaboratories      = $dischargeInstructions->dischargeFollowUpLaboratories()->first();
            $dischargeDoctorsFollowUp           = $dischargeInstructions->dischargeDoctorsFollowUp()->first();

            $patientRegistry                    = $patient->patientRegistry()->first();
            $patientHistory                     = $patientRegistry->history()->first();
            $patientMedicalProcedure            = $patientRegistry->medical_procedures()->first();
            $patientVitalSign                   = $patientRegistry->vitals()->first();
            $patientImmunization                = $patientRegistry->immunizations()->first();
            $patientAdministeredMedicine        = $patientRegistry->administered_medicines()->first();
            $OBGYNHistory                       = $patientRegistry->oBGYNHistory()->first();
            $pregnancyHistory                   = $OBGYNHistory->PatientPregnancyHistory()->first();
            $gynecologicalConditions            = $OBGYNHistory->gynecologicalConditions()->first();
            $allergy                            = $patientRegistry->allergies()->first();
            $causeOfAllergy                     = $allergy->cause_of_allergy()->first();
            $symptomsOfAllergy                  = $allergy->symptoms_allergy()->first();
            $badHabits                          = $patientRegistry->bad_habits()->first();
            $patientDoctors                     = $patientRegistry->patientDoctors()->first();
            $physicalAbdomen                    = $patientRegistry->physicalAbdomen()->first();
            $pertinentSignAndSymptoms           = $patientRegistry->pertinentSignAndSymptoms()->first();
            $physicalExamtionChestLungs         = $patientRegistry->physicalExamtionChestLungs()->first();
            $courseInTheWard                    = $patientRegistry->courseInTheWard()->first();
            $physicalExamtionCVS                = $patientRegistry->physicalExamtionCVS()->first();
            $physicalExamtionGeneralSurvey      = $patientRegistry->PhysicalExamtionGeneralSurvey()->first();
            $physicalExamtionHEENT              = $patientRegistry->physicalExamtionHEENT()->first();
            $physicalGUIE                       = $patientRegistry->physicalGUIE()->first();
            $physicalNeuroExam                  = $patientRegistry->physicalNeuroExam()->first();
            $physicalSkinExtremities            = $patientRegistry->physicalSkinExtremities()->first();
            $medications                        = $patientRegistry->medications()->first();

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
                'patient_Id'            => $patient_id,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? $pastImmunization->vaccine_Id,
                'administration_Date'   => $request->payload['administration_Date'] ?? $pastImmunization->administration_Date,
                'dose'                  => $request->payload['dose'] ?? $pastImmunization->dose,
                'site'                  => $request->payload['site'] ?? $pastImmunization->site,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? $pastImmunization->administrator_Name,
                'notes'                 => $request->payload['notes'] ?? $pastImmunization->notes,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPastMedicalHistoryData = [
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? $pastMedicalHistory->diagnose_Description,
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? $pastMedicalHistory->diagnosis_Date,
                'treament'                  => $request->payload['treament'] ?? $pastMedicalHistory->treament,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPastMedicalProcedureData = [
                'patient_Id'                => $patient_id,
                'description'               => $request->payload['description'] ??  $pastMedicalProcedure->description,
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ??  $pastMedicalProcedure->date_Of_Procedure,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPastAllergyHistoryData = [
                'patient_Id'                => $patient_id,
                'family_History'            => $request->payload['family_History'] ?? $pastAllergyHistory->family_History,
                'updatedby'                 => Auth()->user()->idnumber, 
                'updated_at'                => Carbon::now(),
            ];

            $patientPastCauseOfAllergyData = [
                // 'history_Id'            => '',
                'allergy_Type_Id'       => $request->payload['allergy_Type_Id'] ?? $pastCauseOfAllergy ->allergy_Type_Id,
                'duration'              => $request->payload['duration'] ?? $pastCauseOfAllergy ->duration,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPastSymptomsOfAllergyData = [
                // 'history_Id'            => '',
                'symptom_Description'   => $request->payload['symptom_Description'] ??$pastSymtomsOfAllergy->symptom_Description,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientAllergyData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'family_History'    => $request->payload['family_History'] ?? $allergy->family_History,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientCauseOfAllergyData = [
                'allergies_Id'      => '',
                'allergy_Type_Id'   => $request->payload['allergy_Type_Id'] ?? ($causeOfAllergy->allergy_Type_Id ?? null),
                'duration'          => $request->payload['duration'] ?? ($causeOfAllergy->duration ?? null),
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientSymptomsOfAllergyData = [
                'allergies_Id'            => '',
                'symptom_Description'   => $request->payload['symptom_Description'] ?? ($symptomsOfAllergy->symptom_Description ?? null),
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientBadHabitsData = [
                'patient_Id'    => $patient_id,
                'case_No'       => $registry_id,
                'description'   => $request->payload['description'] ?? $badHabits->description,
                'updatedby'     => Auth()->user()->idnumber,
                'updated_at'    => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'patient_Id'    => $patient_id,
                'description'   => $request->payload['description'] ?? $pastBadHabits->description,
                'updatedby'     => Auth()->user()->idnumber,
                'updated_at'    => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'patient_Id'        => $patient_id,
                'drug_Description'  => $request->payload['drug_Description'] ?? $drugUsedForAllergy->drug_Description,
                'hospital'          => $request->payload['hospital'] ?? $drugUsedForAllergy->hospital,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientDoctorsData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => $request->payload['doctor_Id'] ?? $patientDoctors->doctor_Id,
                'doctors_Fullname'  => $request->payload['doctors_Fullname'] ?? $patientDoctors->doctors_Fullname,
                'role_Id'           => $request->payload['role_Id'] ?? $patientDoctors->role_Id,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientPhysicalAbdomenData = [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? $physicalAbdomen->essentially_Normal,
                'palpable_Masses'           => $request->payload['palpable_Masses'] ?? $physicalAbdomen->palpable_Masses,
                'abdominal_Rigidity'        => $request->payload['abdominal_Rigidity'] ?? $physicalAbdomen->abdominal_Rigidity,
                'uterine_Contraction'       => $request->payload['uterine_Contraction'] ?? $physicalAbdomen->uterine_Contraction,
                'hyperactive_Bowel_Sounds'  => $request->payload['hyperactive_Bowel_Sounds'] ?? $physicalAbdomen->hyperactive_Bowel_Sounds,
                'others_Description'        => $request->payload['others_Description'] ?? $physicalAbdomen->others_Description,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPertinentSignAndSymptomsData = [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'altered_Mental_Sensorium'          => $request->payload['altered_Mental_Sensorium'] ?? $pertinentSignAndSymptoms->altered_Mental_Sensorium,
                'abdominal_CrampPain'               => $request->payload['abdominal_CrampPain'] ?? $pertinentSignAndSymptoms->abdominal_CrampPain,
                'anorexia'                          => $request->payload['anorexia'] ?? $pertinentSignAndSymptoms->anorexia,
                'bleeding_Gums'                     => $request->payload['bleeding_Gums'] ?? $pertinentSignAndSymptoms->bleeding_Gums,
                'body_Weakness'                     => $request->payload['body_Weakness'] ?? $pertinentSignAndSymptoms->body_Weakness,
                'blurring_Of_Vision'                => $request->payload['blurring_Of_Vision'] ?? $pertinentSignAndSymptoms->blurring_Of_Vision,
                'chest_PainDiscomfort'              => $request->payload['chest_PainDiscomfort'] ?? $pertinentSignAndSymptoms->chest_PainDiscomfort,
                'constipation'                      => $request->payload['constipation'] ?? $pertinentSignAndSymptoms->constipation,
                'cough'                             => $request->payload['cough'] ?? $pertinentSignAndSymptoms->cough,
                'diarrhea'                          => $request->payload['diarrhea'] ?? $pertinentSignAndSymptoms->diarrhea,
                'dizziness'                         => $request->payload['dizziness'] ?? $pertinentSignAndSymptoms->dizziness,
                'dysphagia'                         => $request->payload['dysphagia'] ?? $pertinentSignAndSymptoms->dysphagia,
                'dysuria'                           => $request->payload['dysuria'] ?? $pertinentSignAndSymptoms->dysuria,
                'epistaxis'                         => $request->payload['epistaxis'] ?? $pertinentSignAndSymptoms->epistaxis,
                'fever'                             => $request->payload['fever'] ?? $pertinentSignAndSymptoms->fever,
                'frequency_Of_Urination'            => $request->payload['frequency_Of_Urination'] ?? $pertinentSignAndSymptoms->frequency_Of_Urination,
                'headache'                          => $request->payload['headache'] ?? $pertinentSignAndSymptoms->headache,
                'hematemesis'                       => $request->payload['hematemesis'] ?? $pertinentSignAndSymptoms->hematemesis,
                'hematuria'                         => $request->payload['hematuria'] ?? $pertinentSignAndSymptoms->hematuria,
                'hemoptysis'                        => $request->payload['hemoptysis'] ?? $pertinentSignAndSymptoms->hemoptysis,
                'irritability'                      => $request->payload['irritability'] ?? $pertinentSignAndSymptoms->irritability,
                'jaundice'                          => $request->payload['jaundice'] ?? $pertinentSignAndSymptoms->jaundice,
                'lower_Extremity_Edema'             => $request->payload['lower_Extremity_Edema'] ?? $pertinentSignAndSymptoms->lower_Extremity_Edema,
                'myalgia'                           => $request->payload['myalgia'] ?? $pertinentSignAndSymptoms->myalgia,
                'orthopnea'                         => $request->payload['orthopnea'] ?? $pertinentSignAndSymptoms->orthopnea,
                'pain'                              => $request->payload['pain'] ?? $pertinentSignAndSymptoms->pain,
                'pain_Description'                  => $request->payload['pain_Description'] ?? $pertinentSignAndSymptoms->pain_Description,
                'palpitations'                      => $request->payload['palpitations'] ?? $pertinentSignAndSymptoms->palpitations,
                'seizures'                          => $request->payload['seizures'] ?? $pertinentSignAndSymptoms->seizures,
                'skin_rashes'                       => $request->payload['skin_rashes'] ?? $pertinentSignAndSymptoms->skin_rashes,
                'stool_BloodyBlackTarry_Mucoid'     => $request->payload['stool_BloodyBlackTarry_Mucoid'] ?? $pertinentSignAndSymptoms->stool_BloodyBlackTarry_Mucoid,
                'sweating'                          => $request->payload['sweating'] ?? $pertinentSignAndSymptoms->sweating,
                'urgency'                           => $request->payload['urgency'] ?? $pertinentSignAndSymptoms->urgency,
                'vomitting'                         => $request->payload['vomitting'] ?? $pertinentSignAndSymptoms->vomitting,
                'weightloss'                        => $request->payload['weightloss'] ?? $pertinentSignAndSymptoms->weightloss,
                'others'                            => $request->payload['others'] ?? $pertinentSignAndSymptoms->others,
                'others_Description'                => $request->payload['others_Description'] ?? $pertinentSignAndSymptoms->others_Description,
                'updatedby'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now(),
            ];

            $patientPhysicalExamtionChestLungsData = [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'essentially_Normal'                    => $request->payload['essentially_Normal'] ?? $physicalExamtionChestLungs->essentially_Normal,
                'lumps_Over_Breasts'                    => $request->payload['lumps_Over_Breasts'] ?? $physicalExamtionChestLungs->lumps_Over_Breasts,
                'asymmetrical_Chest_Expansion'          => $request->payload['asymmetrical_Chest_Expansion'] ?? $physicalExamtionChestLungs->asymmetrical_Chest_Expansion,
                'rales_Crackles_Rhonchi'                => $request->payload['rales_Crackles_Rhonchi'] ?? $physicalExamtionChestLungs->rales_Crackles_Rhonchi,
                'decreased_Breath_Sounds'               => $request->payload['decreased_Breath_Sounds'] ?? $physicalExamtionChestLungs->decreased_Breath_Sounds,
                'intercostalrib_Clavicular_Retraction'  => $request->payload['intercostalrib_Clavicular_Retraction'] ?? $physicalExamtionChestLungs->intercostalrib_Clavicular_Retraction,
                'wheezes'                               => $request->payload['wheezes'] ?? $physicalExamtionChestLungs->wheezes,
                'others_Description'                    => $request->payload['others_Description'] ?? $physicalExamtionChestLungs->others_Description,
                'updatedby'                             => Auth()->user()->idnumber,
                'updated_at'                            => Carbon::now(),
            ];

            $patientCourseInTheWardData = [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                  => $request->payload['doctors_OrdersAction'] ?? $courseInTheWard->doctors_OrdersAction,
                'updatedby'                             => Auth()->user()->idnumber,
                'updated_at'                            => Carbon::now(),
            ];

            $patientPhysicalExamtionCVSData = [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? $physicalExamtionCVS->essentially_Normal,
                'irregular_Rhythm'          => $request->payload['irregular_Rhythm'] ?? $physicalExamtionCVS->irregular_Rhythm,
                'displaced_Apex_Beat'       => $request->payload['displaced_Apex_Beat'] ?? $physicalExamtionCVS->displaced_Apex_Beat,
                'muffled_Heart_Sounds'      => $request->payload['muffled_Heart_Sounds'] ?? $physicalExamtionCVS->muffled_Heart_Sounds,
                'heaves_AndOR_Thrills'      => $request->payload['heaves_AndOR_Thrills'] ?? $physicalExamtionCVS->heaves_AndOR_Thrills,
                'murmurs'                   => $request->payload['murmurs'] ?? $physicalExamtionCVS->murmurs,
                'pericardial_Bulge'         => $request->payload['pericardial_Bulge'] ?? $physicalExamtionCVS->pericardial_Bulge,
                'others_Description'        => $request->payload['others_Description'] ?? $physicalExamtionCVS->others_Description,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? $physicalExamtionGeneralSurvey->awake_And_Alert,
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? $physicalExamtionGeneralSurvey->altered_Sensorium,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPhysicalExamtionHEENTData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? $physicalExamtionHEENT->essentially_Normal,
                'icteric_Sclerae'               => $request->payload['icteric_Sclerae'] ?? $physicalExamtionHEENT->icteric_Sclerae,
                'abnormal_Pupillary_Reaction'   => $request->payload['abnormal_Pupillary_Reaction'] ?? $physicalExamtionHEENT->abnormal_Pupillary_Reaction,
                'pale_Conjunctive'              => $request->payload['pale_Conjunctive'] ?? $physicalExamtionHEENT->pale_Conjunctive,
                'cervical_Lympadenopathy'       => $request->payload['cervical_Lympadenopathy'] ?? $physicalExamtionHEENT->cervical_Lympadenopathy,
                'sunken_Eyeballs'               => $request->payload['sunken_Eyeballs'] ?? $physicalExamtionHEENT->sunken_Eyeballs,
                'dry_Mucous_Membrane'           => $request->payload['dry_Mucous_Membrane'] ?? $physicalExamtionHEENT->dry_Mucous_Membrane,
                'sunken_Fontanelle'             => $request->payload['sunken_Fontanelle'] ?? $physicalExamtionHEENT->sunken_Fontanelle,
                'others_description'            => $request->payload['others_description'] ?? $physicalExamtionHEENT->others_description,
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalGUIEData = [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'essentially_Normal'                => $request->payload['essentially_Normal'] ?? $physicalGUIE->essentially_Normal,
                'blood_StainedIn_Exam_Finger'       => $request->payload['blood_StainedIn_Exam_Finger'] ?? $physicalGUIE->blood_StainedIn_Exam_Finger,
                'cervical_Dilatation'               => $request->payload['cervical_Dilatation'] ?? $physicalGUIE->cervical_Dilatation,
                'presence_Of_AbnormalDischarge'     => $request->payload['presence_Of_AbnormalDischarge'] ?? $physicalGUIE->presence_Of_AbnormalDischarge,
                'others_Description'                => $request->payload['others_Description'] ?? $physicalGUIE->others_Description,
                'updatedby'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now(),
            ];

            $patientPhysicalNeuroExamData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? $physicalNeuroExam->essentially_Normal,
                'abnormal_Reflexes'             => $request->payload['abnormal_Reflexes'] ?? $physicalNeuroExam->abnormal_Reflexes,
                'abormal_Gait'                  => $request->payload['abormal_Gait'] ?? $physicalNeuroExam->abormal_Gait,
                'poor_Altered_Memory'           => $request->payload['poor_Altered_Memory'] ?? $physicalNeuroExam->poor_Altered_Memory,
                'abnormal_Position_Sense'       => $request->payload['abnormal_Position_Sense'] ?? $physicalNeuroExam->abnormal_Position_Sense,
                'poor_Muscle_Tone_Strength'     => $request->payload['poor_Muscle_Tone_Strength'] ?? $physicalNeuroExam->poor_Muscle_Tone_Strength,
                'abnormal_Decreased_Sensation'  => $request->payload['abnormal_Decreased_Sensation'] ?? $physicalNeuroExam->abnormal_Decreased_Sensation,
                'poor_Coordination'             => $request->payload['poor_Coordination'] ?? $physicalNeuroExam->poor_Coordination,
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalSkinExtremitiesData = [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? $physicalSkinExtremities->essentially_Normal,
                'edema_Swelling'            => $request->payload['edema_Swelling'] ?? $physicalSkinExtremities->edema_Swelling,
                'rashes_Petechiae'          => $request->payload['rashes_Petechiae'] ?? $physicalSkinExtremities->rashes_Petechiae,
                'clubbing'                  => $request->payload['clubbing'] ?? $physicalSkinExtremities->clubbing,
                'decreased_Mobility'        => $request->payload['decreased_Mobility'] ?? $physicalSkinExtremities->decreased_Mobility,
                'weak_Pulses'               => $request->payload['weak_Pulses'] ?? $physicalSkinExtremities->weak_Pulses,
                'cold_Clammy_Skin'          => $request->payload['cold_Clammy_Skin'] ?? $physicalSkinExtremities->cold_Clammy_Skin,
                'pale_Nailbeds'             => $request->payload['pale_Nailbeds'] ?? $physicalSkinExtremities->pale_Nailbeds,
                'cyanosis_Mottled_Skin'     => $request->payload['cyanosis_Mottled_Skin'] ?? $physicalSkinExtremities->cyanosis_Mottled_Skin,
                'poor_Skin_Turgor'          => $request->payload['poor_Skin_Turgor'] ?? $physicalSkinExtremities->poor_Skin_Turgor,
                'others_Description'        => $request->payload['others_Description'] ?? $physicalSkinExtremities->others_Description,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];
            
            $patientOBGYNHistoryData = [
                'patient_Id'                                            => $patient_id,
                'case_No'                                               => $registry_id,
                'obsteric_Code'                                         => $request->payload['obsteric_Code'] ?? $OBGYNHistory->obsteric_Code,
                'menarchAge'                                            => $request->payload['menarchAge'] ?? $OBGYNHistory->menarchAge,
                'menopauseAge'                                          => $request->payload['menopauseAge'] ?? $OBGYNHistory->menopauseAge,
                'cycleLength'                                           => $request->payload['cycleLength'] ?? $OBGYNHistory->cycleLength,
                'cycleRegularity'                                       => $request->payload['cycleRegularity'] ?? $OBGYNHistory->cycleRegularity,
                'lastMenstrualPeriod'                                   => $request->payload['lastMenstrualPeriod'] ?? $OBGYNHistory->lastMenstrualPeriod,
                'contraceptiveUse'                                      => $request->payload['contraceptiveUse'] ?? $OBGYNHistory->contraceptiveUse,
                'lastPapSmearDate'                                      => $request->payload['lastPapSmearDate'] ?? $OBGYNHistory->lastPapSmearDate,
                'isVitalSigns_Normal'                                   => $request->payload['isVitalSigns_Normal'] ?? $OBGYNHistory->isVitalSigns_Normal,
                'isAscertainPresent_PregnancyisLowRisk'                 => $request->payload['isAscertainPresent_PregnancyisLowRisk'] ?? $OBGYNHistory->isAscertainPresent_PregnancyisLowRisk,
                'riskfactor_MultiplePregnancy'                          => $request->payload['riskfactor_MultiplePregnancy'] ?? $OBGYNHistory->riskfactor_MultiplePregnancy,
                'riskfactor_OvarianCyst'                                => $request->payload['riskfactor_OvarianCyst'] ?? $OBGYNHistory->riskfactor_OvarianCyst,
                'riskfactor_MyomaUteri'                                 => $request->payload['riskfactor_MyomaUteri'] ?? $OBGYNHistory->riskfactor_MyomaUteri,
                'riskfactor_PlacentaPrevia'                             => $request->payload['riskfactor_PlacentaPrevia'] ?? $OBGYNHistory->riskfactor_PlacentaPrevia,
                'riskfactor_Historyof3Miscarriages'                     => $request->payload['riskfactor_Historyof3Miscarriages'] ?? $OBGYNHistory->riskfactor_Historyof3Miscarriages,
                'riskfactor_HistoryofStillbirth'                        => $request->payload['riskfactor_HistoryofStillbirth'] ?? $OBGYNHistory->riskfactor_HistoryofStillbirth,
                'riskfactor_HistoryofEclampsia'                         => $request->payload['riskfactor_HistoryofEclampsia'] ?? $OBGYNHistory->riskfactor_HistoryofEclampsia,
                'riskfactor_PrematureContraction'                       => $request->payload['riskfactor_PrematureContraction'] ?? $OBGYNHistory->riskfactor_PrematureContraction,
                'riskfactor_NotApplicableNone'                          => $request->payload['riskfactor_NotApplicableNone'] ?? $OBGYNHistory->riskfactor_NotApplicableNone,
                'medicalSurgical_Hypertension'                          => $request->payload['medicalSurgical_Hypertension'] ?? $OBGYNHistory->medicalSurgical_Hypertension,
                'medicalSurgical_HeartDisease'                          => $request->payload['medicalSurgical_HeartDisease'] ?? $OBGYNHistory->medicalSurgical_HeartDisease,
                'medicalSurgical_Diabetes'                              => $request->payload['medicalSurgical_Diabetes'] ?? $OBGYNHistory->medicalSurgical_Diabetes,
                'medicalSurgical_ThyroidDisorder'                       => $request->payload['medicalSurgical_ThyroidDisorder'] ?? $OBGYNHistory->medicalSurgical_ThyroidDisorder,
                'medicalSurgical_Obesity'                               => $request->payload['medicalSurgical_Obesity'] ?? $OBGYNHistory->medicalSurgical_Obesity,
                'medicalSurgical_ModerateToSevereAsthma'                => $request->payload['medicalSurgical_ModerateToSevereAsthma'] ?? $OBGYNHistory->medicalSurgical_ModerateToSevereAsthma,
                'medicalSurigcal_Epilepsy'                              => $request->payload['medicalSurigcal_Epilepsy'] ?? $OBGYNHistory->medicalSurigcal_Epilepsy,
                'medicalSurgical_RenalDisease'                          => $request->payload['medicalSurgical_RenalDisease'] ?? $OBGYNHistory->medicalSurgical_RenalDisease,
                'medicalSurgical_BleedingDisorder'                      => $request->payload['medicalSurgical_BleedingDisorder'] ?? $OBGYNHistory->medicalSurgical_BleedingDisorder,
                'medicalSurgical_HistoryOfPreviousCesarianSection'      => $request->payload['medicalSurgical_HistoryOfPreviousCesarianSection'] ?? $OBGYNHistory->medicalSurgical_HistoryOfPreviousCesarianSection,
                'medicalSurgical_HistoryOfUterineMyomectomy'            => $request->payload['medicalSurgical_HistoryOfUterineMyomectomy'] ?? $OBGYNHistory->medicalSurgical_HistoryOfUterineMyomectomy,
                'medicalSurgical_NotApplicableNone'                     => $request->payload['medicalSurgical_NotApplicableNone'] ?? $OBGYNHistory->medicalSurgical_NotApplicableNone,
                'deliveryPlan_OrientationToMCP'                         => $request->payload['deliveryPlan_OrientationToMCP'] ?? $OBGYNHistory->deliveryPlan_OrientationToMCP,
                'deliveryPlan_ExpectedDeliveryDate'                     => $request->payload['deliveryPlan_ExpectedDeliveryDate'] ?? $OBGYNHistory->deliveryPlan_ExpectedDeliveryDate,
                'followUp_Prenatal_ConsultationNo_2nd'                  => $request->payload['followUp_Prenatal_ConsultationNo_2nd'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_2nd,
                'followUp_Prenatal_DateVisit_2nd'                       => $request->payload['followUp_Prenatal_DateVisit_2nd'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_2nd,
                'followUp_Prenatal_AOGInWeeks_2nd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_2nd'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_2nd,
                'followUp_Prenatal_Weight_2nd'                          => $request->payload['followUp_Prenatal_Weight_2nd'] ?? $OBGYNHistory->followUp_Prenatal_Weight_2nd,
                'followUp_Prenatal_CardiacRate_2nd'                     => $request->payload['followUp_Prenatal_CardiacRate_2nd'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_2nd,
                'followUp_Prenatal_RespiratoryRate_2nd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_2nd'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_2nd,
                'followUp_Prenatal_BloodPresureSystolic_2nd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_2nd'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_2nd,
                'followUp_Prenatal_BloodPresureDiastolic_2nd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_2nd'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_2nd,
                'followUp_Prenatal_Temperature_2nd'                     => $request->payload['followUp_Prenatal_Temperature_2nd'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_2nd,
                'followUp_Prenatal_ConsultationNo_3rd'                  => $request->payload['followUp_Prenatal_ConsultationNo_3rd'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_3rd,
                'followUp_Prenatal_DateVisit_3rd'                       => $request->payload['followUp_Prenatal_DateVisit_3rd'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_3rd,
                'followUp_Prenatal_AOGInWeeks_3rd'                      => $request->payload['followUp_Prenatal_AOGInWeeks_3rd'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_3rd,
                'followUp_Prenatal_Weight_3rd'                          => $request->payload['followUp_Prenatal_Weight_3rd'] ?? $OBGYNHistory->followUp_Prenatal_Weight_3rd,
                'followUp_Prenatal_CardiacRate_3rd'                     => $request->payload['followUp_Prenatal_CardiacRate_3rd'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_3rd,
                'followUp_Prenatal_RespiratoryRate_3rd'                 => $request->payload['followUp_Prenatal_RespiratoryRate_3rd'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_3rd,
                'followUp_Prenatal_BloodPresureSystolic_3rd'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_3rd'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_3rd,
                'followUp_Prenatal_BloodPresureDiastolic_3rd'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_3rd'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_3rd,
                'followUp_Prenatal_Temperature_3rd'                     => $request->payload['followUp_Prenatal_Temperature_3rd'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_3rd,
                'followUp_Prenatal_ConsultationNo_4th'                  => $request->payload['followUp_Prenatal_ConsultationNo_4th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_4th,
                'followUp_Prenatal_DateVisit_4th'                       => $request->payload['followUp_Prenatal_DateVisit_4th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_4th,
                'followUp_Prenatal_AOGInWeeks_4th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_4th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_4th,
                'followUp_Prenatal_Weight_4th'                          => $request->payload['followUp_Prenatal_Weight_4th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_4th,
                'followUp_Prenatal_CardiacRate_4th'                     => $request->payload['followUp_Prenatal_CardiacRate_4th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_4th,
                'followUp_Prenatal_RespiratoryRate_4th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_4th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_4th,
                'followUp_Prenatal_BloodPresureSystolic_4th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_4th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_4th,
                'followUp_Prenatal_ConsultationNo_5th'                  => $request->payload['followUp_Prenatal_ConsultationNo_5th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_5th,
                'followUp_Prenatal_DateVisit_5th'                       => $request->payload['followUp_Prenatal_DateVisit_5th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_5th,
                'followUp_Prenatal_AOGInWeeks_5th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_5th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_5th,
                'followUp_Prenatal_Weight_5th'                          => $request->payload['followUp_Prenatal_Weight_5th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_5th,
                'followUp_Prenatal_CardiacRate_5th'                     => $request->payload['followUp_Prenatal_CardiacRate_5th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_5th,
                'followUp_Prenatal_RespiratoryRate_5th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_5th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_5th,
                'followUp_Prenatal_BloodPresureSystolic_5th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_5th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_5th,
                'followUp_Prenatal_BloodPresureDiastolic_5th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_5th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_5th,
                'followUp_Prenatal_Temperature_5th'                     => $request->payload['followUp_Prenatal_Temperature_5th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_5th,
                'followUp_Prenatal_ConsultationNo_6th'                  => $request->payload['followUp_Prenatal_ConsultationNo_6th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_6th,
                'followUp_Prenatal_DateVisit_6th'                       => $request->payload['followUp_Prenatal_DateVisit_6th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_6th,
                'followUp_Prenatal_AOGInWeeks_6th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_6th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_6th,
                'followUp_Prenatal_Weight_6th'                          => $request->payload['followUp_Prenatal_Weight_6th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_6th,
                'followUp_Prenatal_CardiacRate_6th'                     => $request->payload['followUp_Prenatal_CardiacRate_6th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_6th,
                'followUp_Prenatal_RespiratoryRate_6th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_6th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_6th,
                'followUp_Prenatal_BloodPresureSystolic_6th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_6th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_6th,
                'followUp_Prenatal_BloodPresureDiastolic_6th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_6th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_6th,
                'followUp_Prenatal_Temperature_6th'                     => $request->payload['followUp_Prenatal_Temperature_6th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_6th,
                'followUp_Prenatal_ConsultationNo_7th'                  => $request->payload['followUp_Prenatal_ConsultationNo_7th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_7th,
                'followUp_Prenatal_DateVisit_7th'                       => $request->payload['followUp_Prenatal_DateVisit_7th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_7th,
                'followUp_Prenatal_AOGInWeeks_7th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_7th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_7th,
                'followUp_Prenatal_Weight_7th'                          => $request->payload['followUp_Prenatal_Weight_7th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_7th,
                'followUp_Prenatal_CardiacRate_7th'                     => $request->payload['followUp_Prenatal_CardiacRate_7th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_7th,
                'followUp_Prenatal_RespiratoryRate_7th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_7th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_7th,
                'followUp_Prenatal_BloodPresureDiastolic_7th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_7th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_7th,
                'followUp_Prenatal_Temperature_7th'                     => $request->payload['followUp_Prenatal_Temperature_7th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_7th,
                'followUp_Prenatal_ConsultationNo_8th'                  => $request->payload['followUp_Prenatal_ConsultationNo_8th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_8th,
                'followUp_Prenatal_DateVisit_8th'                       => $request->payload['followUp_Prenatal_DateVisit_8th'] ?? null,
                // 'followUp_Prenatal_DateVisit_8th'                       => $request->payload['followUp_Prenatal_DateVisit_8th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_8th,
                'followUp_Prenatal_AOGInWeeks_8th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_8th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_8th,
                'followUp_Prenatal_Weight_8th'                          => $request->payload['followUp_Prenatal_Weight_8th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_8th,
                'followUp_Prenatal_CardiacRate_8th'                     => $request->payload['followUp_Prenatal_CardiacRate_8th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_8th,
                'followUp_Prenatal_RespiratoryRate_8th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_8th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_8th,
                'followUp_Prenatal_BloodPresureSystolic_8th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_8th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_8th,
                'followUp_Prenatal_BloodPresureDiastolic_8th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_8th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_8th,
                'followUp_Prenatal_Temperature_8th'                     => $request->payload['followUp_Prenatal_Temperature_8th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_8th,
                'followUp_Prenatal_ConsultationNo_9th'                  => $request->payload['followUp_Prenatal_ConsultationNo_9th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_8th,
                'followUp_Prenatal_DateVisit_9th'                       => $request->payload['followUp_Prenatal_DateVisit_9th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_9th,
                'followUp_Prenatal_AOGInWeeks_9th'                      => $request->payload['followUp_Prenatal_AOGInWeeks_9th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_9th,
                'followUp_Prenatal_Weight_9th'                          => $request->payload['followUp_Prenatal_Weight_9th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_9th,
                'followUp_Prenatal_CardiacRate_9th'                     => $request->payload['followUp_Prenatal_CardiacRate_9th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_9th,
                'followUp_Prenatal_RespiratoryRate_9th'                 => $request->payload['followUp_Prenatal_RespiratoryRate_9th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_9th,
                'followUp_Prenatal_BloodPresureSystolic_9th'            => $request->payload['followUp_Prenatal_BloodPresureSystolic_9th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_9th,
                'followUp_Prenatal_BloodPresureDiastolic_9th'           => $request->payload['followUp_Prenatal_BloodPresureDiastolic_9th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_9th,
                'followUp_Prenatal_Temperature_9th'                     => $request->payload['followUp_Prenatal_Temperature_9th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_9th,
                'followUp_Prenatal_ConsultationNo_10th'                 => $request->payload['followUp_Prenatal_ConsultationNo_10th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_10th,
                'followUp_Prenatal_DateVisit_10th'                      => $request->payload['followUp_Prenatal_DateVisit_10th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_10th,
                'followUp_Prenatal_AOGInWeeks_10th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_10th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_10th,
                'followUp_Prenatal_Weight_10th'                         => $request->payload['followUp_Prenatal_Weight_10th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_10th,
                'followUp_Prenatal_CardiacRate_10th'                    => $request->payload['followUp_Prenatal_CardiacRate_10th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_10th,
                'followUp_Prenatal_RespiratoryRate_10th'                => $request->payload['followUp_Prenatal_RespiratoryRate_10th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_10th,
                'followUp_Prenatal_BloodPresureSystolic_10th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_10th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_10th,
                'followUp_Prenatal_BloodPresureDiastolic_10th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_10th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_10th,
                'followUp_Prenatal_Temperature_10th'                    => $request->payload['followUp_Prenatal_Temperature_10th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_10th,
                'followUp_Prenatal_ConsultationNo_11th'                 => $request->payload['followUp_Prenatal_ConsultationNo_11th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_11th,
                'followUp_Prenatal_DateVisit_11th'                      => $request->payload['followUp_Prenatal_DateVisit_11th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_11th,
                'followUp_Prenatal_AOGInWeeks_11th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_11th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_11th,
                'followUp_Prenatal_Weight_11th'                         => $request->payload['followUp_Prenatal_Weight_11th'] ?? $OBGYNHistory->followUp_Prenatal_Weight_11th,
                'followUp_Prenatal_CardiacRate_11th'                    => $request->payload['followUp_Prenatal_CardiacRate_11th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_11th,
                'followUp_Prenatal_RespiratoryRate_11th'                => $request->payload['followUp_Prenatal_RespiratoryRate_11th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_11th,
                'followUp_Prenatal_BloodPresureSystolic_11th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_11th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_11th,
                'followUp_Prenatal_BloodPresureDiastolic_11th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_11th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_11th,
                'followUp_Prenatal_Temperature_11th'                    => $request->payload['followUp_Prenatal_Temperature_11th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_11th,
                'followUp_Prenatal_ConsultationNo_12th'                 => $request->payload['followUp_Prenatal_ConsultationNo_12th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_12th,
                'followUp_Prenatal_DateVisit_12th'                      => $request->payload['followUp_Prenatal_DateVisit_12th'] ?? $OBGYNHistory->followUp_Prenatal_DateVisit_12th,
                'followUp_Prenatal_AOGInWeeks_12th'                     => $request->payload['followUp_Prenatal_AOGInWeeks_12th'] ?? $OBGYNHistory->followUp_Prenatal_AOGInWeeks_12th,
                'followUp_Prenatal_Weight_12th'                         => $request->payload['ffollowUp_Prenatal_Weight_12th'] ?? $OBGYNHistory->ffollowUp_Prenatal_Weight_12th,
                'followUp_Prenatal_CardiacRate_12th'                    => $request->payload['followUp_Prenatal_CardiacRate_12th'] ?? $OBGYNHistory->followUp_Prenatal_CardiacRate_12th,
                'followUp_Prenatal_RespiratoryRate_12th'                => $request->payload['followUp_Prenatal_RespiratoryRate_12th'] ?? $OBGYNHistory->followUp_Prenatal_RespiratoryRate_12th,
                'followUp_Prenatal_BloodPresureSystolic_12th'           => $request->payload['followUp_Prenatal_BloodPresureSystolic_12th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureSystolic_12th,
                'followUp_Prenatal_BloodPresureDiastolic_12th'          => $request->payload['followUp_Prenatal_BloodPresureDiastolic_12th'] ?? $OBGYNHistory->followUp_Prenatal_BloodPresureDiastolic_12th,
                'followUp_Prenatal_Temperature_12th'                    => $request->payload['followUp_Prenatal_Temperature_12th'] ?? $OBGYNHistory->followUp_Prenatal_Temperature_12th,
                'followUp_Prenatal_Remarks'                             => $request->payload['followUp_Prenatal_Remarks'] ?? $OBGYNHistory->followUp_Prenatal_Remarks,
                'updatedby'                                             => Auth()->user()->idnumber,
                'updated_at'                                            => Carbon::now(),
            ];

            $patientPregnancyHistoryData = [
                // 'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => $request->payload['outcome'] ?? $pregnancyHistory->outcome,
                'deliveryDate'      => $request->payload['deliveryDate'] ?? $pregnancyHistory->deliveryDate,
                'complications'     => $request->payload['complications'] ?? $pregnancyHistory->complications,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditionsData = [
                // 'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? $gynecologicalConditions->diagnosisDate,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now(),
            ];

            $patientMedicationsData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => $request->payload['item_Id'] ?? $medications->item_Id,
                'drug_Description'      => $request->payload['drug_Description'] ?? $medications->drug_Description,
                'dosage'                => $request->payload['dosage'] ?? $medications->dosage,
                'reason_For_Use'        => $request->payload['reason_For_Use'] ?? $medications->reason_For_Use,
                'adverse_Side_Effect'   => $request->payload['adverse_Side_Effect'] ?? $medications->adverse_Side_Effect,
                'hospital'              => $request->payload['hospital'] ?? $medications->hospital,
                'isPrescribed'          => $request->payload['isPrescribed'] ?? $medications->isPrescribed,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now(),
            ];

            $patientPrivilegedCard = [
                'patient_Id'            => $patient_id,
                'card_number'           => $request->payload['card_number'] ?? $privilegedCard->card_number,
                'card_Type_Id'          => $request->payload['card_Type_Id'] ?? $privilegedCard->card_Type_Id,
                'card_BenefitLevel'     => $request->payload['card_BenefitLevel'] ?? $privilegedCard->card_BenefitLevel,
                'card_PIN'              => $request->payload['card_PIN'] ?? $privilegedCard->card_PIN,
                'card_Bardcode'         => $request->payload['card_Bardcode'] ?? $privilegedCard->card_Bardcode,
                'card_RFID'             => $request->payload['card_RFID'] ?? $privilegedCard->card_RFID,
                'card_Balance'          => $request->payload['card_Balance'] ?? $privilegedCard->card_Balance,
                'issued_Date'           => $request->payload['issued_Date'] ?? $privilegedCard->issued_Date,
                'expiry_Date'           => $request->payload['expiry_Date'] ?? $privilegedCard->expiry_Date,
                'points_Earned'         => $request->payload['points_Earned'] ?? $privilegedCard->points_Earned,
                'points_Transferred'    => $request->payload['points_Transferred'] ?? $privilegedCard->points_Transferred,
                'points_Redeemed'       => $request->payload['points_Redeemed'] ?? $privilegedCard->points_Redeemed,
                'points_Forfeited'      => $request->payload['points_Forfeited'] ?? $privilegedCard->points_Forfeited,
                'card_Status'           => $request->payload['card_Status'] ?? $privilegedCard->card_Status,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientPrivilegedPointTransfers = [
                // 'fromCard_Id'       => '',
                'toCard_Id'         => $request->payload['toCard_Id'] ?? $privilegedPointTransfers->toCard_Id,
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? $privilegedPointTransfers->description,
                'points'            => $request->payload['points'] ?? $privilegedPointTransfers->points,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ];

            $patientPrivilegedPointTransactions = [
                // 'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? $privilegedPointTransactions->transaction_Type,
                'description'       => $request->payload['description'] ?? $privilegedPointTransactions->description,
                'points'            => $request->payload['points'] ?? $privilegedPointTransactions->points,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ]; 

            $patientDischargeInstructions = [
                'branch_Id'                         => $request->payload['branch_Id'] ?? $dischargeInstructions->branch_Id,
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'general_Instructions'              => $request->payload['general_Intructions'] ?? $dischargeInstructions->general_Instructions,
                'dietary_Instructions'              => $request->payload['dietary_Intructions'] ?? $dischargeInstructions->dietary_Instructions,
                'medications_Instructions'          => $request->payload['medications_Intructions'] ?? $dischargeInstructions->medications_Instructions,
                'activity_Restriction'              => $request->payload['activity_Restriction'] ?? $dischargeInstructions->activity_Restriction,
                'dietary_Restriction'               => $request->payload['dietary_Restriction'] ?? $dischargeInstructions->dietary_Restriction,
                'addtional_Notes'                   => $request->payload['addtional_Notes'] ?? $dischargeInstructions->addtional_Notes,
                'clinicalPharmacist_OnDuty'         => $request->payload['clinicalPharmacist_OnDuty'] ?? $dischargeInstructions->clinicalPharmacist_OnDuty,
                'clinicalPharmacist_CheckTime'      => $request->payload['clinicalPharmacist_CheckTime'] ?? $dischargeInstructions->clinicalPharmacist_CheckTime,
                'nurse_OnDuty'                      => $request->payload['nurse_OnDuty'] ?? $dischargeInstructions->nurse_OnDuty,
                'intructedBy_clinicalPharmacist'    => $request->payload['intructedBy_clinicalPharmacist'] ?? $dischargeInstructions->intructedBy_clinicalPharmacist,
                'intructedBy_Dietitians'            => $request->payload['intructedBy_Dietitians'] ?? $dischargeInstructions->intructedBy_Dietitians,
                'intructedBy_Nurse'                 => $request->payload['intructedBy_Nurse'] ?? $dischargeInstructions->intructedBy_Nurse,
                'updatedby'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now()
            ];

            $patientDischargeMedications = [
                'instruction_Id'        => $dischargeMedications->instruction_Id,
                'Item_Id'               => $request->payload['Item_Id'] ?? $dischargeMedications->Item_Id,
                'medication_Name'       => $request->payload['medication_Name'] ?? $dischargeMedications->medication_Name,
                'medication_Type'       => $request->payload['medication_Type'] ?? $dischargeMedications->medication_Type,
                'dosage'                => $request->payload['dosage'] ?? $dischargeMedications->dosage,
                'frequency'             => $request->payload['frequency'] ?? $dischargeMedications->frequency,
                'purpose'               => $request->payload['purpose'] ?? $dischargeMedications->purpose,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpTreatment = [
                'instruction_Id'        => $dischargeFollowUpTreatment->instruction_Id,
                'treatment_Description' => $request->payload['treatment_Description'] ?? $dischargeFollowUpTreatment->treatment_Description,
                'treatment_Date'        => $request->payload['treatment_Date'] ?? $dischargeFollowUpTreatment->treatment_Date,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? $dischargeFollowUpTreatment->doctor_Id,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? $dischargeFollowUpTreatment->doctor_Name,
                'notes'                 => $request->payload['notes'] ?? $dischargeFollowUpTreatment->notes,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpLaboratories = [
                'instruction_Id'    => $dischargeFollowUpLaboratories->instruction_Id,
                'item_Id'           => $request->payload['item_Id'] ?? $dischargeFollowUpLaboratories->item_Id,
                'test_Name'         => $request->payload['test_Name'] ?? $dischargeFollowUpLaboratories->test_Name,
                'test_DateTime'     => $request->payload['test_DateTime'] ?? $dischargeFollowUpLaboratories->test_DateTime,
                'notes'             => $request->payload['notes'] ?? $dischargeFollowUpLaboratories->notes,
                'updatedby'         => Auth()->user()->idnumber,
                'updated_at'        => Carbon::now()
            ];

            $patientDischargeDoctorsFollowUp = [
                'instruction_Id'        => $dischargeDoctorsFollowUp->instruction_Id,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? $dischargeDoctorsFollowUp->doctor_Id,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? $dischargeDoctorsFollowUp->doctor_Name,
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? $dischargeDoctorsFollowUp->doctor_Specialization,
                'schedule_Date'         => $request->payload['schedule_Date'] ?? $dischargeDoctorsFollowUp->schedule_Date,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => Carbon::now()
            ];

            $patientHistoryData = [
                'branch_Id'                                 => $request->payload['branch_Id'] ?? 1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'brief_History'                             => $request->payload['brief_History'] ?? $patientHistory->brief_History,
                'pastMedical_History'                       => $request->payload['pastMedical_History'] ?? $patientHistory->pastMedical_History,
                'family_History'                            => $request->payload['family_History'] ?? $patientHistory->family_History,
                'personalSocial_History'                    => $request->payload['personalSocial_History'] ?? $patientHistory->personalSocial_History,
                'chief_Complaint_Description'               => $request->payload['chief_Complaint_Description'] ?? $patientHistory->chief_Complaint_Description,
                'impression'                                => $request->payload['impression'] ?? $patientHistory->impression,
                'admitting_Diagnosis'                       => $request->payload['admitting_Diagnosis'] ?? $patientHistory->admitting_Diagnosis,
                'discharge_Diagnosis'                       => $request->payload['discharge_Diagnosis'] ?? $patientHistory->discharge_Diagnosis,
                'preOperative_Diagnosis'                    => $request->payload['preOperative_Diagnosis'] ?? $patientHistory->preOperative_Diagnosis,
                'postOperative_Diagnosis'                   => $request->payload['postOperative_Diagnosis'] ?? $patientHistory->postOperative_Diagnosis,
                'surgical_Procedure'                        => $request->payload['surgical_Procedure'] ?? $patientHistory->surgical_Procedure,
                'physicalExamination_Skin'                  => $request->payload['physicalExamination_Skin'] ?? $patientHistory->physicalExamination_Skin,
                'physicalExamination_HeadEyesEarsNeck'      => $request->payload['physicalExamination_HeadEyesEarsNeck'] ?? $patientHistory->physicalExamination_HeadEyesEarsNeck,
                'physicalExamination_Neck'                  => $request->payload['physicalExamination_Neck'] ?? $patientHistory->physicalExamination_Neck,
                'physicalExamination_ChestLungs'            => $request->payload['physicalExamination_ChestLungs'] ?? $patientHistory->physicalExamination_ChestLungs,
                'physicalExamination_CardioVascularSystem'  => $request->payload['physicalExamination_CardioVascularSystem'] ?? $patientHistory->physicalExamination_CardioVascularSystem,
                'physicalExamination_Abdomen'               => $request->payload['physicalExamination_Abdomen'] ?? $patientHistory->physicalExamination_Abdomen,
                'physicalExamination_GenitourinaryTract'    => $request->payload['physicalExamination_GenitourinaryTract'] ?? $patientHistory->physicalExamination_GenitourinaryTract,
                'physicalExamination_Rectal'                => $request->payload['physicalExamination_Rectal'] ?? $patientHistory->physicalExamination_Rectal,
                'physicalExamination_Musculoskeletal'       => $request->payload['physicalExamination_Musculoskeletal'] ?? $patientHistory->physicalExamination_Musculoskeletal,
                'physicalExamination_LympNodes'             => $request->payload['physicalExamination_LympNodes'] ?? $patientHistory->physicalExamination_LympNodes,
                'physicalExamination_Extremities'           => $request->payload['physicalExamination_Extremities'] ?? $patientHistory->physicalExamination_Extremities,
                'physicalExamination_Neurological'          => $request->payload['physicalExamination_Neurological'] ?? $patientHistory->physicalExamination_Neurological,
                'updatedby'                                 => Auth()->user()->idnumber,
                'updated_at'                                => Carbon::now()
            ];

            $patientMedicalProcedureData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => $request->payload['description'] ?? $patientMedicalProcedure->description,
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? $patientMedicalProcedure->date_Of_Procedure,
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? $patientMedicalProcedure->performing_Doctor_Id,
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? $patientMedicalProcedure->performing_Doctor_Fullname,
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now()
            ];

            $patientVitalSignsData = [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,         
                'transDate'                 => $today,
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  $patientVitalSign->bloodPressureSystolic,
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : $patientVitalSign->bloodPressureDiastolic,
                'temperature'               => isset($request->payload['temperatue']) ? (int)$request->payload['temperatue'] : $patientVitalSign->temperature,
                'pulseRate'                 => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : $patientVitalSign->pulseRate,
                'respiratoryRate'           => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : $patientVitalSign->respiratoryRate,
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : $patientVitalSign->oxygenSaturation,
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => $today
            ];

            $patientRegistryData = [
                'branch_Id'                     => $request->payload['branch_Id'] ?? $patientRegistry->branch_Id,
                'patient_Id'                    => $request->payload['patient_id'] ?? $patientRegistry->patient_Id,
                'case_No'                       => $registry_id,     
                'register_Source'               => $request->payload['register_source'] ?? $patientRegistry->register_Source,
                'register_Casetype'             => $request->payload['register_Casetype'] ?? $patientRegistry->register_Casetype,
                'patient_Age'                   => $request->payload['age'] ?? $patientRegistry->patient_Age,
                'mscAccount_Type'               => $request->payload['mscAccount_type'] ?? $patientRegistry->mscAccount_Type,
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? $patientRegistry->mscAccount_Discount_Id,
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_Trans_Types'] ?? $patientRegistry->mscAccount_Trans_Types,  
                // 'mscPatient_Category'           => $request->payload['mscPatient_category'] ?? $patientRegistry->mscPatient_Category,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? $patientRegistry->mscPrice_Groups,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? $patientRegistry->mscPrice_Schemes,
                'mscService_Type'               => $request->payload['mscService_Type'] ?? $patientRegistry->mscService_Type,
                'queue_Number'                  => $request->payload['queue_number'] ?? $patientRegistry->queue_Number,
                'arrived_Date'                  => $request->payload['arrived_date'] ?? $patientRegistry->arrived_Date,
                'registry_Userid'               => Auth()->user()->idnumber,
                'registry_Date'                 => $today,
                'registry_Status'               => $request->payload['registry_Status'] ?? $patientRegistry->registry_Status,
                'discharged_Userid'             => $request->payload['discharged_Userid'] ?? $patientRegistry->discharged_Userid,
                'discharged_Date'               => $request->payload['discharged_Date'] ?? $patientRegistry->discharged_Date,
                'billed_Userid'                 => $request->payload['billed_Userid'] ?? $patientRegistry->billed_Userid,
                'billed_Date'                   => $request->payload['billed_Date'] ?? $patientRegistry->billed_Date,
                'mscBroughtBy_Relationship_Id'  => $request->payload['mscBroughtBy_Relationship_Id'] ?? $patientRegistry->mscBroughtBy_Relationship_Id,
                'mscCase_Indicators_Id'         => $request->payload['mscCase_Indicators_Id'] ?? $patientRegistry->mscCase_Indicators_Id,
                'billed_Remarks'                => $request->payload['billed_Remarks'] ?? $patientRegistry->billed_Remarks,
                'mgh_Userid'                    => $request->payload['mgh_Userid'] ?? $patientRegistry->mgh_Userid,
                'mgh_Datetime'                  => $request->payload['mgh_Datetime'] ?? $patientRegistry->mgh_Datetime,
                'untag_Mgh_Userid'              => $request->payload['untag_Mgh_Userid'] ?? $patientRegistry->untag_Mgh_Userid,
                'untag_Mgh_Datetime'            => $request->payload['untag_Mgh_Datetime'] ?? $patientRegistry->untag_Mgh_Datetime,
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? $patientRegistry->isHoldReg,
                'hold_Userid'                   => $request->payload['hold_Userid'] ?? $patientRegistry->hold_Userid,
                'hold_No'                       => $request->payload['hold_No'] ?? $patientRegistry->hold_No,
                'hold_Date'                     => $request->payload['hold_Date'] ?? $patientRegistry->hold_Date,
                'hold_Remarks'                  => $request->payload['hold_Remarks'] ?? $patientRegistry->hold_Remarks,
                'isRevoked'                     => $request->payload['isRevoked'] ?? $patientRegistry->isRevoked,
                'revokedBy'                     => $request->payload['revokedBy'] ?? $patientRegistry->revokedBy,
                'revoked_Date'                  => $request->payload['revoked_Date'] ?? $patientRegistry->revoked_Date,
                'revoked_Remarks'               => $request->payload['revoked_Remarks'] ?? $patientRegistry->revoked_Remarks,
                'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patientRegistry->guarantor_Id,
                'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? $patientRegistry->guarantor_Name,
                'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? $patientRegistry->guarantor_Approval_code,
                'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? $patientRegistry->guarantor_Approval_no,
                'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? $patientRegistry->guarantor_Approval_date,
                'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? $patientRegistry->guarantor_Validity_date,
                'guarantor_Approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? $patientRegistry->guarantor_Approval_remarks,
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false) ?? $patientRegistry->isWithCreditLimit,
                'guarantor_Credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? $patientRegistry->guarantor_Credit_Limit,
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? $patientRegistry->isWithPhilHealth,
                'philhealth_Number'             => $request->payload['philhealth_Number'] ?? $patientRegistry->philhealth_Number,
                'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? $patientRegistry->isWithMedicalPackage,
                'medical_Package_Id'            => $request->payload['Medical_Package_id'] ?? $patientRegistry->medical_Package_Id,
                'medical_Package_Name'          => $request->payload['medical_Package_Name'] ?? $patientRegistry->medical_Package_Name,
                'medical_Package_Amount'        => $request->payload['medical_Package_Amount'] ?? $patientRegistry->medical_Package_Amount,
                // 'chief_Complaint_Description'   => $request->payload['clinical_chief_complaint'] ?? $patientRegistry->chief_Complaint_Description,
                'impression'                    => $request->payload['impression'] ?? $patientRegistry->impression,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? $patientRegistry->isCriticallyIll,
                'illness_Type'                  => $request->payload['illness_Type'] ?? $patientRegistry->illness_Type,
                'isHemodialysis'                => $isHemodialysis ?? $patientRegistry->isHemodialysis,
                'isPeritoneal'                  => $isPeritoneal ?? $patientRegistry->isPeritoneal,
                'isLINAC'                       => $isLINAC ?? $patientRegistry->isLINAC,
                'isCOBALT'                      => $isCOBALT ?? $patientRegistry->isCOBALT,
                'isBloodTrans'                  => $isBloodTrans ?? $patientRegistry->isBloodTrans,
                'isChemotherapy'                => $isChemotherapy ?? $patientRegistry->isChemotherapy,
                'isBrachytherapy'               => $isBrachytherapy ?? $patientRegistry->isBrachytherapy,
                'isDebridement'                 => $isDebridement ?? $patientRegistry->isDebridement,
                'isTBDots'                      => $isTBDots ?? $patientRegistry->isTBDots,
                'isPAD'                         => $isPAD ?? $patientRegistry->isPAD,
                'isRadioTherapy'                => $isRadioTherapy ?? $patientRegistry->isRadioTherapy,
                'attending_Doctor'              => $request->payload['selectedConsultant'][0]['attending_Doctor'] ?? $patientRegistry->attending_Doctor,
                'attending_Doctor_fullname'     => $request->payload['selectedConsultant'][0]['attending_Doctor_fullname'] ?? $patientRegistry->attending_Doctor_fullname,
                'bmi'                           => isset($request->payload['bmi']) ? (float)$request->payload['bmi'] : $patientRegistry->bmi,
                'weight'                        => isset($request->payload['weight']) ? (float)$request->payload['weight'] : $patientRegistry->weight,
                'weightUnit'                    => $request->payload['weightUnit'] ?? $patientRegistry->weightUnit,
                'height'                        => isset($request->payload['height']) ? (float)$request->payload['height'] : $patientRegistry->height,
                'heightUnit'                    => $request->payload['height_Unit'] ?? $patientRegistry->heightUnit,
                'bloodPressureSystolic'         => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] : $patientRegistry->bloodPressureSystolic,
                'bloodPressureDiastolic'        => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : $patientRegistry->bloodPressureDiastolic,
                'pulseRate'                     => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : $patientRegistry->pulseRate,
                'respiratoryRate'               => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : $patientRegistry->respiratoryRate,
                'oxygenSaturation'              => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : $patientRegistry->oxygenSaturation,
                'isOpenLateCharges'             => $request->payload['LateCharges'] ?? $patientRegistry->isOpenLateCharges,
                'mscCase_Result_Id'             => $request->payload['mscCase_result_id'] ?? $patientRegistry->mscCase_Result_Id,
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? $patientRegistry->isAutopsy,
                'barcode_Image'                 => $request->payload['barcode_Image'] ?? $patientRegistry->barcode_Image,
                'barcode_Code_Id'               => $request->payload['barcode_Code_Id'] ?? $patientRegistry->barcode_Code_Id,
                'barcode_Code_String'           => $request->payload['barcode_Code_String'] ?? $patientRegistry->barcode_Code_String,
                'isWithConsent_DPA'              => $request->payload['isWithConsent_DPA'] ?? $patientRegistry->isWithConsent_DPA,
                'registry_Remarks'              => $request->payload['registry_Remarks'] ?? $patientRegistry->registry_Remarks, 
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => $today
            ];   

            $patientImmunizationsData = [
                'branch_id'             => 1,
                'patient_id'            => $patient_id,
                'case_No'               => $registry_id,          
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? $patientImmunization->vaccine_Id,
                'administration_Date'   => $request->payload['administration_Date'] ?? $patientImmunization->administration_Date,
                'dose'                  => $request->payload['dose'] ?? $patientImmunization->dose,
                'site'                  => $request->payload['site'] ?? $patientImmunization->site,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? $patientImmunization->administrator_Name,
                'Notes'                 => $request->payload['Notes'] ?? $patientImmunization->Notes,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => $today
            ];

            $patientAdministeredMedicineData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => $request->payload['item_Id'] ?? $patientAdministeredMedicine->item_Id,
                'quantity'              => $request->payload['quantity'] ?? $patientAdministeredMedicine->quantity,
                'administered_Date'     => $request->payload['administered_Date'] ?? $patientAdministeredMedicine->administered_Date,
                'administered_By'       => $request->payload['administered_By'] ?? $patientAdministeredMedicine->administered_By,
                'reference_num'         => $request->payload['reference_num'] ?? $patientAdministeredMedicine->reference_num,
                'transaction_num'       => $request->payload['transaction_num'] ?? $patientAdministeredMedicine->transaction_num,
                'updatedby'             => Auth()->user()->idnumber,
                'updated_at'            => $today,
            ];

            $existingPatientRecord = Patient::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();
            
            $patient->update($patientData);
            if ($existingPatientRecord) { 
                $pastImmunization->update($patientPastImmunizationData);
                $pastMedicalHistory->update($patientPastMedicalHistoryData);
                $pastMedicalProcedure->update($patientPastMedicalProcedureData);
                $updateAllergy = $pastAllergyHistory->update($patientPastAllergyHistoryData);
                $pastCauseOfAllergy->update($patientPastCauseOfAllergyData);
                $pastSymtomsOfAllergy->update($patientPastSymptomsOfAllergyData);
                $drugUsedForAllergy->update($patientDrugUsedForAllergyData);
                $pastBadHabits->update($patientPastBadHabitsData);
                $privilegedCard->update($patientPrivilegedCard);
                $privilegedPointTransfers->update($patientPrivilegedPointTransfers);
                $privilegedPointTransactions->update($patientPrivilegedPointTransactions);
                $dischargeInstructions->update($patientDischargeInstructions);
                $dischargeMedications->update($patientDischargeMedications);
                $dischargeFollowUpTreatment->update($patientDischargeFollowUpTreatment);
                $dischargeFollowUpLaboratories->update($patientDischargeFollowUpLaboratories);
                $dischargeDoctorsFollowUp->update($patientDischargeDoctorsFollowUp);
            } else {
                throw new \Exception('ERROR UPDATING PATIENT AND RELATED DATA');
            }

            $existingPatientRegistry = PatientRegistry::where('patient_Id', $patient_id)->whereDate('created_at', $today)->exists();
            if ($existingPatientRegistry) {
                $patientRegistry->update($patientRegistryData);
                $patientHistory->update($patientHistoryData);
                $patientMedicalProcedure->update($patientMedicalProcedureData);
                $patientVitalSign->update($patientVitalSignsData);
                $patientImmunization->update($patientImmunizationsData);
                $patientAdministeredMedicine->update($patientAdministeredMedicineData);
                $OBGYNHistory->update($patientOBGYNHistoryData);
                $pregnancyHistory->update($patientPregnancyHistoryData);
                $gynecologicalConditions->update($patientGynecologicalConditionsData);
                $allergy->update($patientAllergyData);
                // $causeOfAllergy->update($patientCauseOfAllergyData);
                // updateIfNotNull($causeOfAllergy, $patientCauseOfAllergyData);
                // $symptomsOfAllergy->update($patientSymptomsOfAllergyData);
                // updateIfNotNull($symptomsOfAllergy, $patientSymptomsOfAllergyData);
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
            } else {
                throw new \Exception('ERROR UPDATING PATIENT REGISTRY AND RELATED DATA');
            }

            DB::connection('sqlsrv_patient_data')->commit();
            $registry_sequence->update([
                'seq_no' => $registry_sequence->seq_no + 1,
                'recent_generated' => $registry_sequence->seq_no,
            ]);
            return response()->json([
                'message' => 'Outpatient data updated successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
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
