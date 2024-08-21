<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\PatientAdministeredMedicines;
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
            $sequence = SystemSequence::where('code','MPID')->first();
            $registry_sequence = SystemSequence::where('code','MOPD')->first();
            if (!$sequence || !$registry_sequence) {
                throw new \Exception('Sequence not found');
            }

            $registry_id        = $registry_sequence->seq_no;
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

            // $bloodPressureSystolic  = $request->payload['bloodPressureSystolic'] ?? null;
            // $bloodPressureDiastolic = $request->payload['bloodPressureDiastolic'] ?? null;

            $existingPatient = Patient::where('lastname', $request->payload['lastname'])->where('firstname', $request->payload['firstname'])->where('birthdate', $request->payload['birthdate'])->first();
            if ($existingPatient) {
                $patient_id = $existingPatient->patient_id;
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
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? null,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? null,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? null,
                'bldgstreet'                => $request->payload['bldgstreet'] ?? null,
                'region_id'                 => $request->payload['region_id'] ?? null,
                'province_id'               => $request->payload['province_id'] ?? null,
                'municipality_id'           => $request->payload['municipality_id'] ?? null,
                'barangay_id'               => $request->payload['barangay_id'] ?? null,
                'zipcode_id'                => $request->payload['zipcode_id'] ?? null,
                'country_id'                => $request->payload['country_id'] ?? null,
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
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => '',
                'diagnosis_Date'            => '',
                'treament'                  => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(), 
            ];

            $pastientPastMedicalProcedureData =[
                'patient_Id'                => $patient_id,
                'description'               => '',
                'date_Of_Procedure'         => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastAllergyHistoryData =[
                'patient_Id'                => $patient_id,
                'family_History'            => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastCauseOfAllergyData =[
                'history_Id'            => '',
                'allergy_Type_Id'       => '',
                'duration'              => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $pastientPastSymptomsOfAllergyData =[
                'history_Id'            => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientAllergyData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'family_History'    => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientCauseAllergyData = [
                'history_Id'        => '',
                'allergy_Type_Id'   => '',
                'duration'          => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientSymptomsOfAllergy = [
                'history_Id'            => '',
                'symptom_Description'   => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
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

            $patientRegistryData = [
                'branch_Id'                     =>  1,
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'register_source'               => $request->payload['register_source'] ?? null,
                'mscAccount_type'               => $request->payload['mscAccount_type'] ?? '',
                'mscAccount_discount_id'        => $request->payload['mscAccount_discount_id'] ?? null,
                'mscAccount_trans_types'        => $request->payload['mscAccount_trans_types'] ?? 5, 
                'mscPatient_category'           => $request->payload['mscPatient_category'] ?? null,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? null,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? 100,
                'mscService_type'               => $request->payload['mscService_type'] ?? null,
                'mscService_type2'              => $request->payload['mscService_type2'] ?? null,
                'queue_number'                  => $request->payload['queue_number'] ?? null,
                'arrived_date'                  => $request->payload['arrived_date'] ?? null,
                'registry_userid'               => Auth()->user()->idnumber,
                'registry_date'                 => Carbon::now(),
                'registry_status'               => $request->payload['registry_status'] ?? null,
                'discharged_userid'             => $request->payload['discharged_userid'] ?? null,
                'discharged_date'               => $request->payload['discharged_date'] ?? null,
                'billed_userid'                 => $request->payload['billed_userid'] ?? null,
                'billed_date'                   => $request->payload['billed_date'] ?? null,
                'mscBroughtBy_Relationship_Id'  => $request->payload['mscBroughtBy_Relationship_Id'] ?? null,
                'billed_remarks'                => $request->payload['billed_remarks'] ?? null,
                'mgh_userid'                    => $request->payload['mgh_userid'] ?? null,
                'mgh_datetime'                  => $request->payload['mgh_datetime'] ?? null,
                'untag_mgh_userid'              => $request->payload['untag_mgh_userid'] ?? null,
                'untag_mgh_datetime'            => $request->payload['untag_mgh_datetime'] ?? null,
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? false,
                'hold_userid'                   => $request->payload['hold_userid'] ?? null,
                'hold_no'                       => $request->payload['hold_no'] ?? null,
                'hold_date'                     => $request->payload['hold_date'] ?? null,
                'hold_remarks'                  => $request->payload['hold_remarks'] ?? null,
                'isRevoked'                     => $request->payload['isRevoked'] ?? false,
                'revokedBy'                     => $request->payload['revokedBy'] ?? null,
                'revoked_date'                  => $request->payload['revoked_date'] ?? null,
                'revoked_remarks'               => $request->payload['revoked_remarks'] ?? null,
                'guarantor_id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? null,
                'guarantor_name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? null,
                'guarantor_approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? null,
                'guarantor_approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? null,
                'guarantor_approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? null,
                'guarantor_validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? null,
                'guarantor_approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? null,
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
                'guarantor_credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? null,
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? false,
                'philhealth_number'             => $request->payload['philhealth_number'] ?? null,
                'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? false,
                'Medical_Package_id'            => $request->payload['Medical_Package_id'] ?? null,
                'Medical_Package_name'          => $request->payload['Medical_Package_name'] ?? null,
                'Medical_Package_amount'        => $request->payload['Medical_Package_amount'] ?? null,
                // 'chief_complaint_description'   => $request->payload['clinical_chief_complaint'] ?? null,
                'impression'                    => $request->payload['impression'] ?? null,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? false,
                'illness_type'                  => $request->payload['illness_type'] ?? null,
                'isreferredfrom'                => $request->payload['isreferredfrom'] ?? false,
                'referred_from_HCI'             => $request->payload['referred_from_HCI'] ?? null,
                'referred_from_HCI_address'     => $request->payload['referred_from_HCI_address'] ?? null,
                'referred_from_HCI_code'        => $request->payload['referred_from_HCI_code'] ?? null,
                'referring_doctor'              => $request->payload['referring_doctor'] ?? null,
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
                'attending_doctor'              => $request->payload['selectedConsultant'][0]['doctor_code'] ?? null,
                'attending_doctor_fullname'     => $request->payload['selectedConsultant'][0]['doctor_name'] ?? null,
                'mscDisposition_id'             => $request->payload['mscDispositions'] ?? null,
                'heightUnit'                    => $request->payload['height_Unit'] ?? null,
                'weightUnit'                    => $request->payload['weightUnit'] ?? null,
                'bmi'                           => $request->payload['bmi'] ?? null,
                'weight'                        => isset($request->payload['weight']) ? (float)$request->payload['weight'] : null,
                'height'                        => isset($request->payload['height']) ? (float)$request->payload['height'] : null,
                'bloodPressureSystolic'         => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  null,
                'bloodPressureDiastolic'        => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : null,
                'pulseRate'                     => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : null,
                'respiratoryRate'               => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : null,
                'oxygenSaturation'              => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : null,
                'isOpenLateCharges'             => $request->payload['LateCharges'] ?? null,
                'mscCase_result_id'             => $request->payload['mscCase_result_id'] ?? null,
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? false,
                'barcode_image'                 => $request->payload['barcode_image'] ?? null,
                'barcode_code_id'               => $request->payload['barcode_code_id'] ?? null,
                'barcode_code_string'           => $request->payload['barcode_code_string'] ?? null,
                'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? false,
                'registry_remarks'              => $request->payload['registry_remarks'] ?? null,
                'er_Bedno'                      => $request->payload['area_bed_no'] ?? null,
                'CreatedBy'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];    

            $patientBadHabitsData = [
                'patient_Id' => $patient_id,
                'case_No'   => $registry_id,
                'description' => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'patient_Id' => $patient_id,
                'description' => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'patient_Id'        => $patient_id,
                'drug_Description'  => '',
                'hospital'          => '',
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
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
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'obsteric_Code'         => '',
                'MenarchAge'            => '',
                'MenopauseAge'          => '',
                'cycleLength'          => '',
                'CycleRegularity'       => '',
                'LastMenstrualPeriod'   => '',
                'ContraceptiveUse'      => '',
                'LastPapSmearDate'      => '',
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
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

            $patient = Patient::updateOrCreate($patientRule, $patientData);
            if (!$patient) {
                throw new \Exception('Failed to create patient master data');
            } else {
                $patient->past_medical_procedures()->create($pastientPastMedicalProcedureData);
                $patient->past_medical_history()->create($patientPastMedicalHistoryData);
                $patient->past_immunization()->create($patientPastImmunizationData);
                $patient->past_bad_habits()->create($patientPastBadHabitsData);
                $patient->drug_used_for_allergy()->create($patientDrugUsedForAllergyData);
    
                $pastHistory = $patient->past_allergy_history()->create($pastientPastAllergyHistoryData);
                $pastientPastCauseOfAllergyData['history_Id'] =   $pastHistory->id;
                $pastientPastSymptomsOfAllergyData['history_Id'] =   $pastHistory->id;
                $pastHistory->pastCauseOfAllergy()->create($pastientPastCauseOfAllergyData);
                $pastHistory->pastSymptomsOfAllergy()->create($pastientPastSymptomsOfAllergyData);
                $patient->past_allergy_history()->create($pastientPastAllergyHistoryData);
            }

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingRegistry) {
                $patientRegistry = $patient->patientRegistry()->create($patientRegistryData);
                $patientRegistry->history()->create($patientHistoryData);
                $patientRegistry->immunizations()->create($patientImmunizationsData );
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
                $patientCauseAllergyData['history_Id'] = $last_inserted_id;
                $patientSymptomsOfAllergy['history_Id'] = $last_inserted_id;
                $patientAllergy->cause_of_allergy()->create($patientCauseAllergyData);
                $patientAllergy->symptoms_allergy()->create($patientSymptomsOfAllergy);

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
            $patient_id = $patient->patient_id;

            $registry_sequence = SystemSequence::where('code','MOPD')->first();
            if (!$registry_sequence) {
                throw new \Exception('Sequence not found');
            }
            $registry_id        = $registry_sequence->seq_no;

            $patientRegistry = PatientRegistry::where('patient_Id', $patient_id)->first(); 
            $patientVitalSigns = PatientVitalSigns::where('patient_Id', $patient_id)->first();

            $patientIdentifier = $request->payload['patientIdentifier'] ?? null;
            $isHemodialysis = ($patientIdentifier === "Hemo Patient") ? true : false;
            $isPeritoneal = ($patientIdentifier === "Peritoneal Patient") ? true : false;
            $isLINAC = ($patientIdentifier === "LINAC") ? true : false;
            $isCOBALT = ($patientIdentifier === "COBALT") ? true : false;
            $isBloodTrans = ($patientIdentifier === "Blood Trans Patient") ? true : false;
            $isChemotherapy = ($patientIdentifier === "Chemo Patient") ? true : false;
            $isBrachytherapy = ($patientIdentifier === "Brachytherapy Patient") ? true : false;
            $isDebridement = ($patientIdentifier === "Debridement") ? true : false;
            $isTBDots = ($patientIdentifier === "TB DOTS") ? true : false;
            $isPAD = ($patientIdentifier === "PAD Patient") ? true : false;
            $isRadioTherapy = ($patientIdentifier === "Radio Patient") ? true : false;

            $bloodPressureSystolic  = $request->payload['bloodPressureSystolic'] ?? null;
            $bloodPressureDiastolic = $request->payload['bloodPressureDiastolic'] ?? null;

            $patient->update([
                'title_id'                          => $request->payload['title_id'] ?? $patient->title_id,
                'lastname'                          => ucwords($request->payload['lastname']) ?? $patient->lastname,
                'firstname'                         => ucwords($request->payload['firstname']) ?? $patient->firstname,
                'middlename'                        => ucwords($request->payload['middlename']) ?? $patient->middlename,
                'suffix_id'                         => $request->payload['suffix_id'] ?? $patient->suffix_id,
                'birthdate'                         => $request->payload['birthdate'] ?? $patient->birthdate,
                'age'                               => $request->payload['age'] ?? $patient->age,
                'sex_id'                            => $request->payload['sex_id'] ?? $patient->sex_id,
                'nationality_id'                    => $request->payload['nationality_id'] ?? $patient->nationality_id,
                'religion_id'                       => $request->payload['religion_id'] ?? $patient->religion_id,
                'civilstatus_id'                    => $request->payload['civilstatus_id'] ?? $patient->civilstatus_id,
                'typeofbirth_id'                    => $request->payload['typeofbirth_id'] ?? $patient->typeofbirth_id,
                'birthtime'                         => $request->payload['birthtime'] ?? $patient->birthtime,
                'typeofdeath_id'                    => $request->payload['typeofdeath_id'] ?? $patient->typeofdeath_id,
                'timeofdeath'                       => $request->payload['timeofdeath'] ?? $patient->timeofdeath,
                'bloodtype_id'                      => $request->payload['bloodtype_id'] ?? $patient->bloodtype_id,
                'bldgstreet'                        => $request->payload['address']['bldgstreet'] ?? $patient->bldgstreet,
                'region_id'                         => $request->payload['address']['region_id'] ?? $patient->region_id,
                'province_id'                       => $request->payload['address']['province_id'] ?? $patient->province_id,
                'municipality_id'                   => $request->payload['address']['municipality_id'] ?? $patient->municipality_id,
                'barangay_id'                       => $request->payload['address']['barangay_id'] ?? $patient->barangay_id,
                'zipcode_id'                        => $request->payload['address']['zipcode_id'] ?? $patient->zipcode_id,
                'country_id'                        => $request->payload['address']['country_id'] ?? $patient->country_id,
                'occupation'                        => $request->payload['occupation'] ?? $patient->occupation,
                'telephone_number'                  => $request->payload['telephone_number'] ?? $patient->telephone_number,
                'mobile_number'                     => $request->payload['mobile_number'] ?? $patient->mobile_number,
                'email_address'                     => $request->payload['email_address'] ?? $patient->email_address,
                'isSeniorCitizen'                   => $request->payload['isSeniorCitizen'] ?? $patient->isSeniorCitizen,
                'SeniorCitizen_ID_Number'           => $request->payload['SeniorCitizen_ID_Number'] ?? $patient->SeniorCitizen_ID_Number,
                'isPWD'                             => $request->payload['isPWD'] ?? $patient->isPWD,
                'PWD_ID_Number'                     => $request->payload['PWD_ID_Number'] ?? $patient->PWD_ID_Number,
                'isPhilhealth_Member'               => $request->payload['isPhilhealth_Member'] ?? $patient->isPhilhealth_Member,
                'Philhealth_Number'                 => $request->payload['Philhealth_Number'] ?? $patient->Philhealth_Number,
                'isEmployee'                        => $request->payload['isEmployee'] ?? $patient->isEmployee,
                'branch_id'                         => $request->payload['branch_id'] ?? $patient->branch_id,
                'GSIS_Number'                       => $request->payload['GSIS_Number'] ?? $patient->GSIS_Number,
                'SSS_Number'                        => $request->payload['SSS_Number'] ?? $patient->SSS_Number,
                'is_old_patient'                    => $request->payload['is_old_patient'] ?? $patient->is_old_patient,
                'portal_access_uid'                 => $request->payload['portal_access_uid'] ?? $patient->portal_access_uid,
                'portal_access_pwd'                 => $request->payload['portal_access_pwd'] ?? $patient->portal_access_pwd,
                'isBlacklisted'                     => $request->payload['isBlacklisted'] ?? $patient->isBlacklisted,
                'blacklist_reason'                  => $request->payload['blacklist_reason'] ?? $patient->blacklist_reason,
                'isAbscond'                         => $request->payload['isAbscond'] ?? $patient->isAbscond,
                'abscond_details'                   => $request->payload['abscond_details'] ?? $patient->abscond_details,
                'updatedBy'                         => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now(),
            ]);
            
            $patientVitalSigns = [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'transDate'                 => Carbon::now(),
                'bloodPressureSystolic'     => $bloodPressureSystolic,
                'bloodPressureDiastolic'    => $bloodPressureDiastolic,
                'temperature'               => $request->payload['temperature'] ?? null,
                'pulseRate'                 => $request->payload['pulseRate'] ?? null,
                'oxygenSaturation'          => $request->payload['oxygenSaturation'] ?? null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];
            $patientMedicalProcedures = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => '',
                'date_Of_Procedure'             => '',
                'performing_Doctor_id'          => '',
                'performing_Doctor_Fullname'    => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];
            $patientHistory = [
                'branch_Id'                                 => 1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'brief_History'                             => '',
                'pastMedical_History'                       => '',
                'family_History'                            => '',
                'personalSocial_History'                    => '',
                'chief_Complaint_Description'               => '',
                'impression'                                => '',
                'admitting_Diagnosis'                       => '',
                'discharge_Diagnosis'                       => '',
                'preOperative_Diagnosis'                    => '',
                'postOperative_Diagnosis'                   => '',
                'surgical_Procedure'                        => '',
                'physicalExamination_Skin'                  => '',
                'physicalExamination_HeadEyesEarsNeck'      => '',
                'physicalExamination_Neck'                  => '',
                'physicalExamination_ChestLungs'            => '',
                'physicalExamination_CardioVascularSystem'  => '',
                'physicalExamination_Abdomen'               => '',
                'physicalExamination_GenitourinaryTract'    => '',
                'physicalExamination_Rectal'                => '',
                'physicalExamination_Musculoskeletal'       => '',
                'physicalExamination_LympNodes'             => '',
                'physicalExamination_Extremities'           => '',
                'physicalExamination_Neurological'          => '',
                'createdby'                                 => Auth()->user()->idnumber,
                'created_at'                                => Carbon::now(),
                'updatedby'                                 => Auth()->user()->idnumber,
                'updated_at'                                => Carbon::now(),
            ];
            $patientImmunizations = [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'vaccine_Id'                => '',  
                'administration_Date'       => '',
                'dose'                      => '',
                'site'                      => '',
                'administrator_Name'        => '',
                'Notes'                     => '',
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ];
            $patientAdministeredMedicines = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'transactDate'                  => '',
                'item_Id'                       => '',
                'administered_Date'             => '',
                'administered_By'               => '',
                'reference_num'                 => '',
                'transaction_num'               => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];
            $patientRegistry = [
                    'branch_Id'                     => $request->payload['mscBranches_id'] ?? $patientRegistry->branch_Id,
                    'patient_Id'                    => $patient_id,
                    'case_No'                       => $registry_id,
                    'register_Source'               => $request->payload['register_Source'] ?? $patientRegistry->register_Source,
                    'register_Casetype'             => $request->payload['register_Casetype'] ?? $patientRegistry->register_Casetype, 
                    'patient_Age'                   => $request->payload['age'] ?? $patientRegistry->patient_Age,
                    'mscAccount_Type'               => $request->payload['mscAccount_type'] ?? $patientRegistry->mscAccount_Type,
                    'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? $patientRegistry->mscAccount_Discount_Id,
                    'mscAccount_Trans_Types'        => $request->payload['mscAccount_trans_types'] ?? $patientRegistry->mscAccount_Trans_Types, 
                    'mscPatient_Category'           => $patientRegistry->mscPatient_Category,
                    'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? $patientRegistry->mscPrice_Groups,
                    'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? $patientRegistry->mscPrice_Schemes,
                    'queue_Number'                  => $request->payload['queue_number'] ?? $patientRegistry->queue_Number,
                    'arrived_Date'                  => $request->payload['arrived_date'] ?? $patientRegistry->arrived_Date,
                    'registry_Userid'               => Auth()->user()->idnumber,
                    'registry_Date'                 => Carbon::now(),
                    'registry_Status'               => $request->payload['registry_status'] ?? $patientRegistry->registry_Status,
                    'discharged_Userid'             => $request->payload['discharged_userid'] ?? $patientRegistry->discharged_Userid,
                    'discharged_Date'               => $request->payload['discharged_date'] ?? $patientRegistry->discharged_Date,
                    'billed_Userid'                 => $request->payload['billed_userid'] ?? $patientRegistry->billed_Userid,
                    'billed_Date'                   => $request->payload['billed_date'] ?? $patientRegistry->billed_Date,
                    'billed_Remarks'                => $request->payload['billed_remarks'] ?? $patientRegistry->billed_Remarks,
                    'mgh_Userid'                    => $request->payload['mgh_userid'] ?? $patientRegistry->mgh_Userid,
                    'mgh_Datetime'                  => $request->payload['mgh_datetime'] ?? $patientRegistry->mgh_Datetime,
                    'untag_Mgh_Userid'              => $request->payload['untag_mgh_userid'] ?? $patientRegistry->untag_Mgh_Userid,
                    'untag_Mgh_Datetime'            => $request->payload['untag_mgh_datetime'] ?? $patientRegistry->untag_Mgh_Datetime,
                    'isRevoked'                     => $request->payload['isRevoked'] ?? $patientRegistry->isRevoked,
                    'revokedBy'                     => $request->payload['revokedBy'] ?? $patientRegistry->revokedBy,
                    'revoked_Date'                  => $request->payload['revoked_date'] ?? $patientRegistry->revoked_Date,
                    'revoked_Remarks'               => $request->payload['revoked_remarks'] ?? $patientRegistry->revoked_Remarks,
                    'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patientRegistry->guarantor_Id,
                    'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? $patientRegistry->guarantor_Name,
                    'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? $patientRegistry->guarantor_Approval_code,
                    'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? $patientRegistry->guarantor_Approval_no,
                    'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? $patientRegistry->guarantor_Approval_date,
                    'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? $patientRegistry->guarantor_Validity_date,
                    'guarantor_Approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? $patientRegistry->guarantor_Approval_remarks,
                    'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false) ?? $patientRegistry->isWithCreditLimit,
                    'guarantor_Credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? $patientRegistry->guarantor_Credit_Limit,
                    // 'isWithMultiple_Gurantor' 
                    // gurantor_Multiple_TotalCreditLimit
                    'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? $patientRegistry->isWithPhilHealth,
                    'mscPHIC_Membership_Type_id'    => $request->payload['msc_PHIC_Memberships'] ?? $patientRegistry->mscPHIC_Membership_Type_id,
                    'philhealth_Number'             => $request->payload['philhealth_number'] ?? $patientRegistry->philhealth_Number,
                    'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? $patientRegistry->isWithMedicalPackage,
                    'medical_Package_Id'            => $request->payload['Medical_Package_id'] ?? $patientRegistry->medical_Package_Id,
                    'medical_Package_Name'          => $request->payload['Medical_Package_name'] ?? $patientRegistry->medical_Package_Name,
                    'medical_Package_Amount'        => $request->payload['Medical_Package_amount'] ?? $patientRegistry->medical_Package_Amount,
                    'chief_Complaint_Description'   => $request->payload['chief_complaint_description'] ?? $patientRegistry->chief_Complaint_Description,
                    'impression'                    => $request->payload['impression'] ?? $patientRegistry->impression,
                    'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? $patientRegistry->isCriticallyIll,
                    'illness_Type'                  => $request->payload['illness_type'] ?? $patientRegistry->illness_Type,
                    'bloodPressureSystolic'         => $bloodPressureSystolic ?? $patientRegistry->bloodPressureSystolic,
                    'bloodPressureDiastolic'        => $bloodPressureDiastolic ?? $patientRegistry->bloodPressureDiastolic,
                    'temperatute'                   => $request->payload['temperature'] ?? $patientRegistry->temperature,
                    'pulseRate'                     => $request->payload['pulseRate'] ?? $patientRegistry->pulseRate,
                    'respiratoryRate'               => $request->payload['respiratoryRate'] ?? $patientRegistry->respiratoryRate,
                    'oxygenSaturation'              => $request->payload['oxygenSaturation'] ?? $patientRegistry->oxygenSaturation,
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
                    'attending_Doctor'              => $request->payload['selectedConsultant'][0]['attending_doctor'] ?? $patientRegistry->attending_Doctor,
                    'attending_Doctor_fullname'     => $request->payload['selectedConsultant'][0]['attending_doctor_fullname'] ?? $patientRegistry->attending_Doctor_fullname,
                    'bmi'                           => $request->payload['bmi'] ?? $patientRegistry->bmi,
                    'weight'                        => $request->payload['weight'] ?? $patientRegistry->weight,
                    'weightUnit'                    => $request->payload['weightUnit'] ?? $patientRegistry->weightUnit,
                    'height'                        => $request->payload['height'] ?? $patientRegistry->height,
                    'heightUnit'                    => $request->payload['height_Unit'] ?? $patientRegistry->heightUnit,
                    'isOpenLateCharges'             => $request->payload['LateCharges'] ?? $patientRegistry->isOpenLateCharges,
                    'mscdisposition_id'             => $request->payload['mscdisposition_id'] ?? $patientRegistry->mscdisposition_id,
                    'mscCase_result_id'             => $request->payload['mscCase_result_id'] ?? $patientRegistry->mscCase_result_id,
                    'dateofdeath'                   => $request->payload['death_date'] ?? $patientRegistry->dateofdeath,
                    'isdiedless48Hours'             => $request->payload['isdied_less48Hours'] ?? $patientRegistry->isdiedless48Hours,
                    'isAutopsy'                     => $request->payload['isAutopsy'] ?? $patientRegistry->isAutopsy,
                    'barcode_Image'                 => $request->payload['barcode_image'] ?? $patientRegistry->barcode_Image,
                    'barcode_Code_Id'               => $request->payload['barcode_code_id'] ?? $patientRegistry->barcode_Code_Id,
                    'barcode_Code_String'           => $request->payload['barcode_code_string'] ?? $patientRegistry->barcode_Code_String,
                    'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? $patientRegistry->isWithConsent_DPA,
                    'registry_Remarks'              => $request->payload['registry_remarks'] ?? $patientRegistry->registry_Remarks, 
                    'CreatedBy'                     => Auth()->user()->idnumber,
                    'created_at'                    => Carbon::now(),
            ];

            $today = Carbon::now()->format('Y-m-d');
            $existingData = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at' , $today)
                ->exists();

            if ($existingData) {
                $patientRegistry = PatientRegistry::update($patientRegistry);
                $patientVitalSigns = PatientVitalSigns::update($patientVitalSigns);
                $patientMedicalProcedures = PatientMedicalProcedures::update($patientMedicalProcedures);
                $patientHistory = PatientHistory::update($patientHistory);
                $patientImmunizations = PatientImmunizations::update($patientImmunizations);
                $patientAdministeredMedicines = PatientAdministeredMedicines::update($patientAdministeredMedicines);
            } else {
                $patientRegistry = PatientRegistry::create($patientRegistry);
                $patientVitalSigns = PatientVitalSigns::create($patientVitalSigns);
                $patientMedicalProcedures = PatientMedicalProcedures::create($patientMedicalProcedures);
                $patientHistory = PatientHistory::create($patientHistory);
                $patientImmunizations = PatientImmunizations::create($patientImmunizations);
                $patientAdministeredMedicines = PatientAdministeredMedicines::create($patientAdministeredMedicines);
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
                'error' => $e->getMessage()
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
            $patientRegistry = PatientRegistry::where('patient_id', $id)->first();

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
            $patientRegistry = PatientRegistry::where('patient_id', $id)->first();

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
