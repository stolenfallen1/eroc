<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\mscPatientBroughtBy;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientAppointments;
use App\Models\HIS\PatientAppointmentTransactions;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
// use APP\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientPastMedicalHistory;
use App\Models\HIS\PatientPastAllergyHistory;
use App\Models\HIS\PatientBadHabits;
use App\Models\HIS\PatientPastBadHabits;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientPastImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientPastCauseofAllergy;
use App\Models\HIS\PatientPastSymptomsofAllergy;
use App\Models\HIS\PatientPastMedicalProcedures;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\mscComplaint;
use App\Models\HIS\mscServiceType;
use App\Models\HIS\PatientAllergies;
use App\Models\HIS\PatientPrivilegedCard;
use App\Models\HIS\PatientAppointmentsTemporary;
use App\Models\HIS\PatientOBGYNHistory;
use App\Rules\UniquePatientRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetIP;
use App\Helpers\HIS\UpdateIfNotNull;
use Illuminate\Support\Facades\Log;
class EmergencyRegistrationController extends Controller
{
    //
    public function index() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5); 
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
                'message' => 'Failed to get emergency patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPatientBroughtBy() {
        try {
            $data = mscPatientBroughtBy::select('id', 'description')->orderBy('id', 'ASC')->get();
            if($data->isEmpty()) return response()->json([], 404);
            $patientBroughtBy = $data->map(function($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($patientBroughtBy, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }

    public function getDisposition() {
        try {
           $data =  DB::connection('sqlsrv')->table('mscDispositions')
                ->select('id', 'disposition_description')
                ->orderBy('disposition_description','asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $dispositions = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->disposition_description,
                ];
            });
            return response()->json($dispositions, 200);
        } catch(\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }
    public function getComplaintList() {
        try {
            $data = mscComplaint::select('id', 'description')
                ->where('isActive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscComplaints = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscComplaints, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }

    public function getServiceType() {
        try {
            $data = mscServiceType::select('id', 'description')
                ->where('isactive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscServiceType = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscServiceType, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }

     public function register(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $sequence = SystemSequence::where('code','MPID')->first();
            $registry_sequence = SystemSequence::where('code','MERN')->first();
            
            $patient_id             = $request->payload['patient_Id'] ?? $sequence->seq_no;
            $registry_id            = $request->payload['case_No'] ?? $registry_sequence->seq_no;
            $patientIdentifier      = $request->payload['patientIdentifier'] ?? null;
            $isHemodialysis         = ($patientIdentifier === "Hemo Patient") ? true : false;
            $isPeritoneal           = ($patientIdentifier === "Peritoneal Patient") ? true : false;
            $isLINAC                = ($patientIdentifier === "LINAC") ? true : false;
            $isCOBALT               = ($patientIdentifier === "COBALT") ? true : false;
            $isBloodTrans           = ($patientIdentifier === "Blood Trans Patient") ? true : false;
            $isChemotherapy         = ($patientIdentifier === "Chemo Patient") ? true : false;
            $isBrachytherapy        = ($patientIdentifier === "Brachytherapy Patient") ? true : false;
            $isDebridement          = ($patientIdentifier === "Debridement") ? true : false;
            $isTBDots               = ($patientIdentifier === "TB DOTS") ? true : false;
            $isPAD                  = ($patientIdentifier === "PAD Patient") ? true : false;
            $isRadioTherapy         = ($patientIdentifier === "Radio Patient") ? true : false;
        

            $existingPatient = Patient::where('lastname', $request->payload['lastname'])
                ->where('firstname', $request->payload['firstname'])
                ->first();

            if ($existingPatient):
                $patient_id = $existingPatient->patient_Id;
            else:
                $patient_id = $sequence->seq_no;
                $sequence->update([
                    'seq_no'            => $sequence->seq_no + 1,
                    'recent_generated'  => $sequence->seq_no,
                ]);
            endif;

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
                'birthtime'                 => $request->payload['birthtime'] ?? null,
                'birthplace'                => $request->payload['birthplace'] ?? null,
                'age'                       => $request->payload['age'] ?? null,
                'sex_id'                    => $request->payload['sex_id'] ?? null,
                'nationality_id'            => $request->payload['nationality_id'] ?? null,
                'citizenship_id'            => $request->payload['citizenship_id'] ?? null,
                'complexion'                => $request->payload['complexion'] ?? null,
                'haircolor'                 => $request->payload['haircolor'] ?? null,
                'eyecolor'                  => $request->payload['eyecolor'] ?? null,
                'height'                    => $request->payload['height'] ?? null,
                'weight'                    => $request->payload['weight'] ?? null,
                'religion_id'               => $request->payload['religion_id'] ?? null,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? null,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? null,
                'dialect_spoken'            => $request->payload['dialect_spoken'] ?? null,
                'bldgstreet'                => $request->payload['address']['bldgstreet'] ?? null,
                'region_id'                 => $request->payload['address']['region_id'] ?? null,
                'province_id'               => $request->payload['address']['province_id'] ?? null,
                'municipality_id'           => $request->payload['address']['municipality_id'] ?? null,
                'barangay_id'               => $request->payload['address']['barangay_id'] ?? null,
                'country_id'                => $request->payload['address']['country_id'] ?? null,
                'zipcode_id'                => $request->payload['zipcode_id'] ?? null,
                'occupation'                => $request->payload['occupation'] ?? null,
                'dependents'                => $request->payload['dependents'] ?? null,
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
                'passport_number'           => $request->payload['passport_number'] ?? null,
                'seaman_book_number'        => $request->payload['seaman_book_number'] ?? null,
                'embarked_date'             => $request->payload['embarked_date'] ?? null,
                'disembarked_date'          => $request->payload['disembarked_date'] ?? null,
                'xray_number'               => $request->payload['xray_number'] ?? null,
                'ultrasound_number'         => $request->payload['ultrasound_number'] ?? null,
                'ct_number'                 => $request->payload['ct_number'] ?? null,
                'petct_number'              => $request->payload['petct_number'] ?? null,
                'mri_number'                => $request->payload['mri_number'] ?? null,
                'mammo_number'              => $request->payload['mammo_number'] ?? null,
                'OB_number'                 => $request->payload['OB_number'] ?? null,
                'nuclearmed_number'         => $request->payload['nuclearmed_number'] ?? null,
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? null,
                'dateofdeath'               => $request->payload['dateofdeath'] ?? null,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? null,
                'spDateMarried'             => $request->payload['spDateMarried'] ?? null,
                'spLastname'                => $request->payload['spLastname'] ?? null,
                'spFirstname'               => $request->payload['spFirstname'] ?? null,
                'spMiddlename'              => $request->payload['spMiddlename'] ?? null,
                'spSuffix_id'               => $request->payload['spSuffix_id'] ?? null,
                'spAddress'                 => $request->payload['spAddress'] ?? null,
                'sptelephone_number'        => $request->payload['sptelephone_number'] ?? null,
                'spmobile_number'           => $request->payload['spmobile_number'] ?? null,
                'spOccupation'              => $request->payload['spOccupation'] ?? null,
                'spBirthdate'               => $request->payload['spBirthdate'] ?? null,
                'spAge'                     => $request->payload['spAge'] ?? null,
                'motherLastname'            => $request->payload['motherLastname'] ?? null,
                'motherFirstname'           => $request->payload['motherFirstname'] ?? null,
                'motherMiddlename'          => $request->payload['motherMiddlename'] ?? null,
                'motherSuffix_id'           => $request->payload['motherSuffix_id'] ?? null,
                'motherAddress'             => $request->payload['motherAddress'] ?? null,
                'mothertelephone_number'    => $request->payload['mothertelephone_number'] ?? null,
                'mothermobile_number'       => $request->payload['mothermobile_number'] ?? null,
                'motherOccupation'          => $request->payload['motherOccupation'] ?? null, 
                'motherBirthdate'           => $request->payload['motherBirthdate'] ?? null,
                'motherAge'                 => $request->payload['motherAge'] ?? null,
                'fatherLastname'            => $request->payload['fatherLastname'] ?? null,
                'fatherFirstname'           => $request->payload['fatherFirstname'] ?? null,
                'fatherMiddlename'          => $request->payload['fatherMiddlename'] ?? null,
                'fatherSuffix_id'           => $request->payload['fatherSuffix_id'] ?? null,
                'fatherAddress'             => $request->payload['fatherAddress'] ?? null,
                'fathertelephone_number'    => $request->payload['fathertelephone_number'] ?? null,
                'fathermobile_number'       => $request->payload['fathermobile_number'] ?? null,
                'fatherOccupation'          => $request->payload['fatherOccupation'] ?? null,
                'fatherBirthdate'           => $request->payload['fatherBirthdate'] ?? null,
                'fatherAge'                 => $request->payload['fatherAge'] ?? null,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? null,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? null,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? null,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? null,
                'isAbscond'                 => $request->payload['isAbscond'] ?? false,
                'abscond_details'           => $request->payload['abscond_details'] ?? null,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? null,
                'patient_picture'           => $request->payload['patient_picture'] ?? null,
                'patient_picture_path'      => $request->payload['patient_picture_path'] ?? null,
                'branch_id'                 => $request->payload['branch_id'] ?? null,
                'previous_patient_id'       => $request->payload['previous_patient_id'] ?? null,
                'medsys_patient_id'         => $request->payload['medsys_patient_id'] ?? null,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientPastImmunizationData = [
                'branch_Id'             => 1,
                'patient_Id'            => $patient_id,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? null,
                'administration_Date'   => $request->payload['administration_Date'] ?? null,
                'dose'                  => $request->payload['dose'] ?? null,
                'site'                  => $request->payload['site'] ?? null,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? null,
                'notes'                 => $request->payload['notes'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientPastMedicalHistoryData = [
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? null,
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? null,
                'treament'                  => $request->payload['treament'] ?? null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastMedicalProcedureData =[
                'patient_Id'                => $patient_id,
                'description'               => $request->payload['description'] ?? null,
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ?? null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastAllergyHistoryData =[
                'patient_Id'                => $patient_id,
                'family_History'            => $request->payload['family_History'] ?? null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastCauseOfAllergyData =[
                'history_Id'            => '',
                'allergy_Type_Id'       => $request->payload['allergy_Type_Id'] ?? null,
                'duration'              => $request->payload['duration'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $pastientPastSymptomsOfAllergyData =[
                'history_Id'            => '',
                'symptom_Description'   => $request->payload['symptom_Description'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientAllergyData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'family_History'    => $request->payload['family_History'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientCauseAllergyData = [
                'allergies_Id'        => '',
                'allergy_Type_Id'   => $request->payload['allergy_Type_Id'] ?? null,
                'duration'          => $request->payload['duration'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientSymptomsOfAllergy = [
                'allergies_Id'            => '',
                'symptom_Description'   => $request->payload['symptom_Description'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientAdministeredMedicineData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'transactDate'       => Carbon::now(),
                'item_Id'               => $request->payload['item_Id'] ?? null,
                'quantity'              => $request->payload['quantity'] ?? null,
                'administered_Date'     => $request->payload['administered_Date'] ?? null,
                'administered_By'       => $request->payload['administered_By'] ?? null,
                'reference_num'         => $request->payload['reference_num'] ?? null,
                'transaction_num'       => $request->payload['transaction_num'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientHistoryData = [
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
                'createdby'                                 => Auth()->user()->idnumber,
                'created_at'                                => Carbon::now(),
            ];

            $patientImmunizationsData = [
                'branch_id'             => 1,
                'patient_id'            => $patient_id,
                'case_No'               => $registry_id,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? '',
                'administration_Date'   => $request->payload['administration_Date'] ?? null,
                'dose'                  => $request->payload['dose'] ?? null,
                'site'                  => $request->payload['site'] ?? null,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? null,
                'Notes'                 => $request->payload['Notes'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientMedicalProcedureData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => $request->payload['description'] ?? null,
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? null,
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? null,
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? null,
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientVitalSignsData = [
                'branch_Id'                 => 1,
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'transDate'                 => Carbon::now(),
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :   null,
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : null,
                'temperature'               => isset($request->payload['temperatue']) ? (int)$request->payload['temperatue'] : null,
                'pulseRate'                 => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : null,
                'respiratoryRate'           => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : null,
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientRegistryData = [
                'branch_Id'                                 =>  1,
                'patient_Id'                                => $patient_id,
                'case_No'                                   => $registry_id,
                'er_Case_No'                                => $request->payload['er_Case_No'] ?? null,
                'register_source'                           => $request->payload['register_source'] ?? null,
                'register_Casetype'                         => $request->payload['register_Casetype'] ?? null,
                'register_Link_Case_No'                     => $request->payload['register_Link_Case_No'] ?? null,
                'register_Case_No_Consolidate'              => $request->payload['register_Case_No_Consolidate'] ?? null,
                'patient_Age'                               => $request->payload['age'] ?? null,
                'er_Bedno'                                  => $request->payload['er_Bedno'] ?? null,
                'room_Code'                                 => $request->payload['room_Code'] ?? null,
                'room_Rate'                                 => $request->payload['room_Rate'] ?? null,
                'mscAccount_Type'                           => $request->payload['mscAccount_Type'] ?? '',
                'mscAccount_Discount_Id'                    => $request->payload['mscAccount_Discount_Id'] ?? null,
                'mscAccount_Trans_Types'                    => $request->payload['mscAccount_Trans_Types'] ?? 5, 
                'mscAdmission_Type_Id'                      => $request->payload['mscAdmission_Type_Id'] ?? null,
                'mscPatient_Category'                       => $request->payload['mscPatient_Category'] ?? null,
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
                'registry_Userid'                           => Auth()->user()->idnumber,
                'registry_Date'                             => Carbon::now(),
                'registry_Status'                           => $request->payload['registry_Status'] ?? 1,
                'registry_Hostname'                         => $request->payload['registry_Hostname'] ?? null,
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
                'guarantor_Id'                              => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? null,
                'guarantor_Name'                            => $request->payload['selectedGuarantor'][0]['guarantor_Name'] ?? null,
                'guarantor_Approval_code'                   => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? null,
                'guarantor_Approval_no'                     => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? null,
                'guarantor_Approval_date'                   => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? null,
                'guarantor_Validity_date'                   => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? null,
                'guarantor_Approval_remarks'                => $request->payload['guarantor_Approval_remarks'] ?? null,
                'isWithCreditLimit'                         => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
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
                'attending_Doctor'                          => $request->payload['selectedConsultant']['attending_Doctor'] ?? null,
                'attending_Doctor_fullname'                 => $request->payload['selectedConsultant']['attending_Doctor_fullname'] ?? null,
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
                'isHemodialysis'                            => $isHemodialysis,
                'isPeritoneal'                              => $isPeritoneal,
                'isLINAC'                                   => $isLINAC,
                'isCOBALT'                                  => $isCOBALT,
                'isBloodTrans'                              => $isBloodTrans,
                'isChemotherapy'                            => $isChemotherapy,
                'isBrachytherapy'                           => $isBrachytherapy,
                'isDebridement'                             => $isDebridement,
                'isTBDots'                                  => $isTBDots,
                'isPAD'                                     => $isPAD,
                'isRadioTherapy'                            => $isRadioTherapy,
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
                'createdBy'                                 => Auth()->user()->idnumber,
                'created_at'                                => Carbon::now(),           
            ];    

            $patientBadHabitsData = [
                'patient_Id'    => $patient_id,
                'case_No'       => $registry_id,
                'description'   => $request->payload['description'] ?? null,
                'createdby'     => Auth()->user()->idnumber,
                'created_at'    => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'patient_Id'    => $patient_id,
                'description'   => '',
                'createdby'     => Auth()->user()->idnumber,
                'created_at'    => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'patient_Id'        => $patient_id,
                'drug_Description'  => $request->payload['drug_Description'] ?? null,
                'hospital'          => $request->payload['hospital'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientDoctorsData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => $request->payload['doctor_Id'] ?? null,
                'doctors_Fullname'  => $request->payload['doctors_Fullname'] ?? null,
                'role_Id'           => $request->payload['role_Id'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientPhysicalAbdomenData = [
                'patient_Id'                => $patient_id,
                'case_No'                   => $registry_id,
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? null,
                'palpable_Masses'           => $request->payload['palpable_Masses'] ?? null,
                'abdominal_Rigidity'        => $request->payload['abdominal_Rigidity'] ?? null,
                'uterine_Contraction'       => $request->payload['uterine_Contraction'] ?? null,
                'hyperactive_Bowel_Sounds'  => $request->payload['hyperactive_Bowel_Sounds'] ?? null,
                'others_Description'        => $request->payload['others_Description'] ?? null,
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientPertinentSignAndSymptomsData = [
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
                'createdby'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ];

            $patientPhysicalExamtionChestLungsData = [
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
                'createdby'                             => Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ];

            $patientCourseInTheWardData = [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                  => $request->payload['doctors_OrdersAction'] ?? null,
                'createdby'                             => Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
            ];

            $patientPhysicalExamtionCVSData = [
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
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? null,
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientPhysicalExamtionHEENTData = [
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
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientPhysicalGUIEData = [
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'essentially_Normal'                => $request->payload['essentially_Normal'] ?? null,
                'blood_StainedIn_Exam_Finger'       => $request->payload['blood_StainedIn_Exam_Finger'] ?? null,
                'cervical_Dilatation'               => $request->payload['cervical_Dilatation'] ?? null,
                'presence_Of_AbnormalDischarge'     => $request->payload['presence_Of_AbnormalDischarge'] ?? null,
                'others_Description'                => $request->payload['others_Description'] ?? null,
                'createdby'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
            ];

            $patientPhysicalNeuroExamData = [
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
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
            ];

            $patientPhysicalSkinExtremitiesData = [
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
                'createdby'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientOBGYNHistory = [
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
                'createdby'                                             => Auth()->user()->idnumber,
                'created_at'                                            => Carbon::now(),
            ];

            $patientPregnancyHistoryData = [
                'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => $request->payload['outcome'] ?? null,
                'deliveryDate'      => $request->payload['deliveryDate'] ?? null,
                'complications'     => $request->payload['complications'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditions = [
                'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientMedicationsData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'item_Id'               => $request->payload['item_Id'] ?? null,
                'drug_Description'      => $request->payload['drug_Description'] ?? null,
                'dosage'                => $request->payload['dosage'] ?? null,
                'reason_For_Use'        => $request->payload['reason_For_Use'] ?? null,
                'adverse_Side_Effect'   => $request->payload['adverse_Side_Effect'] ?? null,
                'hospital'              => $request->payload['hospital'] ?? null,
                'isPrescribed'          => $request->payload['isPrescribed'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
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

            $privilegedPointTransfers = [
                'fromCard_Id'       => '',
                'toCard_Id'         => $request->payload['toCard_Id'] ?? 4,
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];

            $privilegedPointTransactions = [
                'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];

            $patientDischargeInstructions = [
                'branch_Id'                         => $request->payload['branch_Id'] ?? 1,
                'patient_Id'                        => $patient_id,
                'case_No'                           => $registry_id,
                'general_Instructions'               => $request->payload['general_Intructions'] ?? null,
                'dietary_Instructions'               => $request->payload['dietary_Intructions'] ?? null,
                'medications_Instructions'           => $request->payload['medications_Intructions'] ?? null,
                'activity_Restriction'              => $request->payload['activity_Restriction'] ?? null,
                'dietary_Restriction'               => $request->payload['dietary_Restriction'] ?? null,
                'addtional_Notes'                   => $request->payload['addtional_Notes'] ?? null,
                'clinicalPharmacist_OnDuty'         => $request->payload['clinicalPharmacist_OnDuty'] ?? null,
                'clinicalPharmacist_CheckTime'      => $request->payload['clinicalPharmacist_CheckTime'] ?? null,
                'nurse_OnDuty'                      => $request->payload['nurse_OnDuty'] ?? null,
                'intructedBy_clinicalPharmacist'    => $request->payload['intructedBy_clinicalPharmacist'] ?? null,
                'intructedBy_Dietitians'            => $request->payload['intructedBy_Dietitians'] ?? null,
                'intructedBy_Nurse'                 => $request->payload['intructedBy_Nurse'] ?? null,
                'createdby'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now()
            ];

            $patientDischargeMedications = [
                'instruction_Id'        => '',
                'Item_Id'               => $request->payload['Item_Id'] ?? null,
                'medication_Name'       => $request->payload['medication_Name'] ?? null,
                'medication_Type'       => $request->payload['medication_Type'] ?? null,
                'dosage'                => $request->payload['dosage'] ?? null,
                'frequency'             => $request->payload['frequency'] ?? null,
                'purpose'               => $request->payload['purpose'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpTreatment = [
                'instruction_Id'        => '',
                'treatment_Description' => $request->payload['treatment_Description'] ?? null,
                'treatment_Date'        => $request->payload['treatment_Date'] ?? null,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'notes'                 => $request->payload['notes'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpLaboratories = [
                'instruction_Id'    => '',
                'item_Id'           => $request->payload['item_Id'] ?? null,
                'test_Name'         => $request->payload['test_Name'] ?? null,
                'test_DateTime'     => $request->payload['test_DateTime'] ?? null,
                'notes'             => $request->payload['notes'] ?? null,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now()
            ];

            $patientDischargeDoctorsFollowUp = [
                'instruction_Id'        => '',
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? null,
                'schedule_Date'         => $request->payload['schedule_Date'] ?? null,
                'createdby'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now()
            ];


            $today = Carbon::now()->format('Y-m-d');
            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

            //Insert Data Function
            $patient = Patient::updateOrCreate($patientRule, $patientData);
            $patient->past_medical_procedures()->create($pastientPastMedicalProcedureData);
            $patient->past_medical_history()->create($patientPastMedicalHistoryData);
            $patient->past_immunization()->create($patientPastImmunizationData);
            $patient->past_bad_habits()->create($patientPastBadHabitsData);
            $patient->drug_used_for_allergy()->create($patientDrugUsedForAllergyData);

            $patientPriviledgeCard = $patient->privilegedCard()->create($patientPrivilegedCard);
            $privilegedPointTransfers['fromCard_Id'] = $patientPriviledgeCard->id;
            $privilegedPointTransfers['toCard_Id'] = $patientPriviledgeCard->id;
            $privilegedPointTransactions['card_Id'] = $patientPriviledgeCard->id;
            $patientPriviledgeCard->pointTransactions()->create($privilegedPointTransactions);
            $patientPriviledgeCard->pointTransfers()->create($privilegedPointTransfers);

            $pastHistory = $patient->past_allergy_history()->create($pastientPastAllergyHistoryData);
            $pastientPastCauseOfAllergyData['history_Id'] =   $pastHistory->id;
            $pastientPastSymptomsOfAllergyData['history_Id'] =   $pastHistory->id;
            $pastHistory->pastCauseOfAllergy()->create($pastientPastCauseOfAllergyData);
            $pastHistory->pastSymptomsOfAllergy()->create($pastientPastSymptomsOfAllergyData);
    
            if(!$existingRegistry):
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
                
            else:
                throw new \Exception('Patient already registered today');
            endif;

            if(!$patient || !$patientRegistry):
                throw new \Exception('Error');
            endif;

            if(!isset($request->payload['patient_id'])):
                $sequence->update([
                    'seq_no' => $sequence->seq_no + 1,
                    'recent_generated' => $sequence->seq_no,
                ]);
            endif;

            if(!isset($request->payload['registry_id'])):
                $registry_sequence->update([
                    'seq_no' => $registry_sequence->seq_no + 1,
                    'recent_generated' => $registry_sequence->seq_no,
                ]);
            endif;

            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 201);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to register patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patient    = Patient::findOrFail($id);
            $patient_id = $patient->patient_Id;
            $today = Carbon::now()->format('Y-m-d');
            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
            ->whereDate('created_at', $today)
            ->exists();

            $sequence = SystemSequence::where('code','MPID')->first();
            $registry_sequence = SystemSequence::where('code','MERN')->first();
            $registry_id            = $request->payload['registry_id'] ?? $registry_sequence->seq_no;

            $checkPatient = ['patient_Id' =>  $patient_id];
            $pastImmunization               = $patient->past_immunization()->first();
            $pastMedicalHistory             = $patient->past_medical_history()->first();
            $pastMedicalProcedure           = $patient->past_medical_procedures()->first();
            $pastBadHabits                  = $patient->past_bad_habits()->first();
            $drugUsedForAllergy             = $patient->drug_used_for_allergy()->first();
            $pastAllergyHistory             = $patient->past_allergy_history()->first();  
            $pastCauseOfAllergy             = $pastAllergyHistory->pastCauseOfAllergy()->first();
            $pastSymtomsOfAllergy           = $pastAllergyHistory->pastSymptomsOfAllergy()->first();

            $patientRegistry                = $patient->patientRegistry()->first();

            $patientHistory                 = $patientRegistry->history()->first();
            $patientMedicalProcedure        = $patientRegistry->medical_procedures()->first();
            $patientVitalSign               = $patientRegistry->vitals()->first();
            $patientImmunization            = $patientRegistry->immunizations()->first();
            $patientAdministeredMedicine    = $patientRegistry->administered_medicines()->first();

            $OBGYNHistory                   = $patientRegistry->oBGYNHistory()->first();
            $pregnancyHistory               = $OBGYNHistory->PatientPregnancyHistory()->first();
            $gynecologicalConditions        = $OBGYNHistory->gynecologicalConditions()->first();

            $allergy                        = $patientRegistry->allergies()->first();
            $causeOfAllergy                 = $allergy->cause_of_allergy()->first();
            $symptomsOfAllergy              = $allergy->symptoms_allergy()->first();

            $badHabits                      = $patientRegistry->bad_habits()->first();
            $patientDoctors                 = $patientRegistry->patientDoctors()->first();
            $physicalAbdomen                = $patientRegistry->physicalAbdomen()->first();
            $pertinentSignAndSymptoms       = $patientRegistry->pertinentSignAndSymptoms()->first();
            $physicalExamtionChestLungs     = $patientRegistry->physicalExamtionChestLungs()->first();
            $courseInTheWard                = $patientRegistry->courseInTheWard()->first();
            $physicalExamtionCVS            = $patientRegistry->physicalExamtionCVS()->first();
            $physicalExamtionGeneralSurvey  = $patientRegistry->PhysicalExamtionGeneralSurvey()->first();
            $physicalExamtionHEENT          = $patientRegistry->physicalExamtionHEENT()->first();
            $physicalGUIE                   = $patientRegistry->physicalGUIE()->first();
            $physicalNeuroExam              = $patientRegistry->physicalNeuroExam()->first();
            $physicalSkinExtremities        = $patientRegistry->physicalSkinExtremities()->first();

            $medications                    = $patientRegistry->medications()->first();
            $dischargeInstructions          = $patientRegistry->dischargeInstructions()->first();
            $dischargeMedications           = $dischargeInstructions->dischargeMedications()->first();
            $dischargeFollowUpTreatment     = $dischargeInstructions->dischargeFollowUpTreatment()->first();
            $dischargeFollowUpLaboratories  = $dischargeInstructions->dischargeFollowUpLaboratories()->first();
            $dischargeDoctorsFollowUp       = $dischargeInstructions->dischargeDoctorsFollowUp()->first();

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

            $patientData = [
                'patient_Id'                => $patient_id,
                'title_id'                  => $request->payload['title_id'] ?? $patient->title_id,
                'lastname'                  => ucwords($request->payload['lastname'] ?? $patient->lastname),
                'firstname'                 => ucwords($request->payload['firstname'] ?? $patient->firstname),
                'middlename'                => ucwords($request->payload['middlename'] ?? $patient->middlename),
                'suffix_id'                 => $request->payload['suffix_id'] ?? $patient->suffix_id,
                'birthdate'                 => $request->payload['birthdate'] ?? $patient->birthdate,
                'birthtime'                 => $request->payload['birthtime'] ?? $patient->birthtime,
                'birthplace'                => $request->payload['birthplace'] ?? $patient->birthplace,
                'age'                       => $request->payload['age'] ?? $patient->age,
                'sex_id'                    => $request->payload['sex_id'] ?? $patient->sex_id,
                'nationality_id'            => $request->payload['nationality_id'] ?? $patient->nationality_id,
                'citizenship_id'            => $request->payload['citizenship_id'] ?? $patient->citizenship_id,
                'complexion'                => $request->payload['complexion'] ?? $patient->complexion,
                'haircolor'                 => $request->payload['haircolor'] ?? $patient->haircolor,
                'eyecolor'                  => $request->payload['eyecolor'] ?? $patient->eyecolor,
                'height'                    => $request->payload['height'] ?? $patient->height,
                'weight'                    => $request->payload['weight'] ?? $patient->weight,
                'religion_id'               => $request->payload['religion_id'] ?? $patient->religion_id,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? $patient->civilstatus_id,
                'bloodtype_id'              => $request->payload['bloodtype_id'] ?? $patient->bloodtype_id,
                'dialect_spoken'            => $request->payload['dialect_spoken'] ?? $patient->dialect_spoken,
                'bldgstreet'                => $request->payload['address']['bldgstreet'] ?? $patient->bldgstreet,
                'region_id'                 => $request->payload['address']['region_id'] ?? $patient->region_id,
                'province_id'               => $request->payload['address']['province_id'] ?? $patient->province_id,
                'municipality_id'           => $request->payload['address']['municipality_id'] ?? $patient->municipality_id,
                'barangay_id'               => $request->payload['address']['barangay_id'] ?? $patient->barangay_id,
                'country_id'                => $request->payload['address']['country_id'] ?? $patient->country_id,
                'zipcode_id'                => $request->payload['zipcode_id'] ?? $patient->zipcode_id,
                'occupation'                => $request->payload['occupation'] ?? $patient->occupation,
                'dependents'                => $request->payload['dependents'] ?? $patient->dependents,
                'telephone_number'          => $request->payload['telephone_number'] ?? $patient->telephone_number,
                'mobile_number'             => $request->payload['mobile_number'] ?? $patient->mobile_number,
                'email_address'             => $request->payload['email_address'] ?? $patient->email_address,
                'isSeniorCitizen'           => $request->payload['isSeniorCitizen'] ?? false,
                'SeniorCitizen_ID_Number'   => $request->payload['SeniorCitizen_ID_Number'] ?? $patient->SeniorCitizen_ID_Number,
                'isPWD'                     => $request->payload['isPWD'] ?? false,
                'PWD_ID_Number'             => $request->payload['PWD_ID_Number'] ?? $patient->PWD_ID_Number,
                'isPhilhealth_Member'       => $request->payload['isPhilhealth_Member'] ?? false,
                'Philhealth_Number'         => $request->payload['Philhealth_Number'] ?? $patient->Philhealth_Number,
                'isEmployee'                => $request->payload['isEmployee'] ?? false,
                'GSIS_Number'               => $request->payload['GSIS_Number'] ?? $patient->GSIS_Number,
                'SSS_Number'                => $request->payload['SSS_Number'] ?? $patient->SSS_Number,
                'passport_number'           => $request->payload['passport_number'] ?? $patient->passport_number,
                'seaman_book_number'        => $request->payload['seaman_book_number'] ?? $patient->seaman_book_number,
                'embarked_date'             => $request->payload['embarked_date'] ?? $patient->embarked_date,
                'disembarked_date'          => $request->payload['disembarked_date'] ?? $patient->disembarked_date,
                'xray_number'               => $request->payload['xray_number'] ?? $patient->xray_number,
                'ultrasound_number'         => $request->payload['ultrasound_number'] ?? $patient->ultrasound_number,
                'ct_number'                 => $request->payload['ct_number'] ?? $patient->ct_number,
                'petct_number'              => $request->payload['petct_number'] ?? $patient->petct_number,
                'mri_number'                => $request->payload['mri_number'] ?? $patient->mri_number,
                'mammo_number'              => $request->payload['mammo_number'] ?? $patient->mammo_number,
                'OB_number'                 => $request->payload['OB_number'] ?? $patient->OB_number,
                'nuclearmed_number'         => $request->payload['nuclearmed_number'] ?? $patient->nuclearmed_number,
                'typeofdeath_id'            => $request->payload['typeofdeath_id'] ?? $patient->typeofdeath_id,
                'dateofdeath'               => $request->payload['dateofdeath'] ?? $patient->dateofdeath,
                'timeofdeath'               => $request->payload['timeofdeath'] ?? $patient->timeofdeath,
                'spDateMarried'             => $request->payload['spDateMarried'] ?? $patient->spDateMarried,
                'spLastname'                => $request->payload['spLastname'] ?? $patient->spLastname,
                'spFirstname'               => $request->payload['spFirstname'] ?? $patient->spFirstname,
                'spMiddlename'              => $request->payload['spMiddlename'] ?? $patient->spMiddlename,
                'spSuffix_id'               => $request->payload['spSuffix_id'] ?? $patient->spSuffix_id,
                'spAddress'                 => $request->payload['spAddress'] ?? $patient->spAddress,
                'sptelephone_number'        => $request->payload['sptelephone_number'] ?? $patient->sptelephone_number,
                'spmobile_number'           => $request->payload['spmobile_number'] ?? $patient->spmobile_number,
                'spOccupation'              => $request->payload['spOccupation'] ?? $patient->spOccupation,
                'spBirthdate'               => $request->payload['spBirthdate'] ?? $patient->spBirthdate,
                'spAge'                     => $request->payload['spAge'] ?? $patient->spAge,
                'motherLastname'            => $request->payload['motherLastname'] ?? $patient->motherLastname,
                'motherFirstname'           => $request->payload['motherFirstname'] ?? $patient->motherFirstname,
                'motherMiddlename'          => $request->payload['motherMiddlename'] ?? $patient->motherMiddlename,
                'motherSuffix_id'           => $request->payload['motherSuffix_id'] ?? $patient->motherSuffix_id,
                'motherAddress'             => $request->payload['motherAddress'] ?? $patient->motherAddress,
                'mothertelephone_number'    => $request->payload['mothertelephone_number'] ?? $patient->mothertelephone_number,
                'mothermobile_number'       => $request->payload['mothermobile_number'] ?? $patient->mothermobile_number,
                'motherOccupation'          => $request->payload['motherOccupation'] ?? $patient->motherOccupation, 
                'motherBirthdate'           => $request->payload['motherBirthdate'] ?? $patient->motherBirthdate,
                'motherAge'                 => $request->payload['motherAge'] ?? $patient->motherAge,
                'fatherLastname'            => $request->payload['fatherLastname'] ?? $patient->fatherLastname,
                'fatherFirstname'           => $request->payload['fatherFirstname'] ?? $patient->fatherFirstname,
                'fatherMiddlename'          => $request->payload['fatherMiddlename'] ?? $patient->fatherMiddlename,
                'fatherSuffix_id'           => $request->payload['fatherSuffix_id'] ?? $patient->fatherSuffix_id,
                'fatherAddress'             => $request->payload['fatherAddress'] ?? $patient->fatherAddress,
                'fathertelephone_number'    => $request->payload['fathertelephone_number'] ?? $patient->fathertelephone_number,
                'fathermobile_number'       => $request->payload['fathermobile_number'] ?? $patient->fathermobile_number,
                'fatherOccupation'          => $request->payload['fatherOccupation'] ?? $patient->fatherOccupation,
                'fatherBirthdate'           => $request->payload['fatherBirthdate'] ?? $patient->fatherBirthdate,
                'fatherAge'                 => $request->payload['fatherAge'] ?? $patient->fatherAge,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? $patient->portal_access_uid,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? $patient->portal_access_pwd,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? $patient->isBlacklisted,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? $patient->blacklist_reason,
                'isAbscond'                 => $request->payload['isAbscond'] ?? false,
                'abscond_details'           => $request->payload['abscond_details'] ?? $patient->abscond_details,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? $patient->is_old_patient,
                'patient_picture'           => $request->payload['patient_picture'] ?? $patient->patient_picture,
                'patient_picture_path'      => $request->payload['patient_picture_path'] ?? $patient->patient_picture_path,
                'branch_id'                 => $request->payload['branch_id'] ?? $patient->branch_id,
                'previous_patient_id'       => $request->payload['previous_patient_id'] ?? $patient->previous_patient_id,
                'medsys_patient_id'         => $request->payload['medsys_patient_id'] ?? $patient->medsys_patient_id,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientPastImmunizationData = [
                'branch_Id'             => 1,
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? $pastImmunization->vaccine_Id,
                'administration_Date'   => $request->payload['administration_Date'] ?? $pastImmunization->administration_Date,
                'dose'                  => $request->payload['dose'] ?? $pastImmunization->dose,
                'site'                  => $request->payload['site'] ?? $pastImmunization->site,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? $pastImmunization->administrator_Name,
                'notes'                 => $request->payload['notes'] ?? $pastImmunization->notes,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientPastMedicalHistoryData = [
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? $pastMedicalHistory->diagnose_Description,
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? $pastMedicalHistory->diagnosis_Date,
                'treament'                  => $request->payload['treament'] ?? $pastMedicalHistory->treament,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $pastientPastMedicalProcedureData =[
                'description'               => $request->payload['description'] ??  $pastMedicalProcedure->description,
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ??  $pastMedicalProcedure->date_Of_Procedure,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $pastientPastAllergyHistoryData =[
                'family_History'            => $request->payload['family_History'] ?? $pastAllergyHistory->family_History,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $pastientPastCauseOfAllergyData = [
                'allergy_Type_Id'       => $request->payload['allergy_Type_Id'] ?? $pastCauseOfAllergy->allergy_Type_Id,
                'duration'              => $request->payload['duration'] ?? $pastCauseOfAllergy ->duration,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $pastientPastSymptomsOfAllergyData =[
                'symptom_Description'   => $request->payload['symptom_Description'] ??$pastSymtomsOfAllergy->symptom_Description,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientAllergyData = [
                'family_History'    => $request->payload['family_History'] ?? $allergy->family_History,
                'createdBy'         => Auth()->user()->idnumber,
                'updatedBy'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            $patientCauseAllergyData = [
                'allergies_Id'      => $allergy->id,
                'allergy_Type_Id'   => $request->payload['allergy_Type_Id'] ?? '',
                'duration'          => $request->payload['duration'] ?? '',
                'createdBy'         => Auth()->user()->idnumber,
                'updatedBy'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            $patientSymptomsOfAllergyData = [
                'allergies_Id'              => $allergy->id,
                'symptom_Description'       => $request->payload['symptom_Description'] ?? '',
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientBadHabitsData = [
                'description'   => $request->payload['description'] ?? $badHabits->description,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'description'   => $request->payload['description'] ?? $pastBadHabits->description,
                'createdby'     => Auth()->user()->idnumber,
                'created_at'    => Carbon::now(),
            ];

            $patientDrugUsedForAllergyData = [
                'drug_Description'  => $request->payload['drug_Description'] ?? $drugUsedForAllergy->drug_Description,
                'hospital'          => $request->payload['hospital'] ?? $drugUsedForAllergy->hospital,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientDoctorsData = [
                'doctor_Id'         => $request->payload['doctor_Id'] ?? $patientDoctors->doctor_Id,
                'doctors_Fullname'  => $request->payload['doctors_Fullname'] ?? $patientDoctors->doctors_Fullname,
                'role_Id'           => $request->payload['role_Id'] ?? $patientDoctors->role_Id,
                'createdby'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientPhysicalAbdomenData = [
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? $physicalAbdomen->essentially_Normal,
                'palpable_Masses'           => $request->payload['palpable_Masses'] ?? $physicalAbdomen->palpable_Masses,
                'abdominal_Rigidity'        => $request->payload['abdominal_Rigidity'] ?? $physicalAbdomen->abdominal_Rigidity,
                'uterine_Contraction'       => $request->payload['uterine_Contraction'] ?? $physicalAbdomen->uterine_Contraction,
                'hyperactive_Bowel_Sounds'  => $request->payload['hyperactive_Bowel_Sounds'] ?? $physicalAbdomen->hyperactive_Bowel_Sounds,
                'others_Description'        => $request->payload['others_Description'] ?? $physicalAbdomen->others_Description,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientPertinentSignAndSymptomsData = [
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
                'createdBy'                         => Auth()->user()->idnumber,
                'updatedBy'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
                'updated_at'                        => Carbon::now(),   
            ];

            $patientPhysicalExamtionChestLungsData = [
                'essentially_Normal'                    => $request->payload['essentially_Normal'] ?? $physicalExamtionChestLungs->essentially_Normal,
                'lumps_Over_Breasts'                    => $request->payload['lumps_Over_Breasts'] ?? $physicalExamtionChestLungs->lumps_Over_Breasts,
                'asymmetrical_Chest_Expansion'          => $request->payload['asymmetrical_Chest_Expansion'] ?? $physicalExamtionChestLungs->asymmetrical_Chest_Expansion,
                'rales_Crackles_Rhonchi'                => $request->payload['rales_Crackles_Rhonchi'] ?? $physicalExamtionChestLungs->rales_Crackles_Rhonchi,
                'decreased_Breath_Sounds'               => $request->payload['decreased_Breath_Sounds'] ?? $physicalExamtionChestLungs->decreased_Breath_Sounds,
                'intercostalrib_Clavicular_Retraction'  => $request->payload['intercostalrib_Clavicular_Retraction'] ?? $physicalExamtionChestLungs->intercostalrib_Clavicular_Retraction,
                'wheezes'                               => $request->payload['wheezes'] ?? $physicalExamtionChestLungs->wheezes,
                'others_Description'                    => $request->payload['others_Description'] ?? $physicalExamtionChestLungs->others_Description,
                'createdBy'                             => Auth()->user()->idnumber,
                'updatedBy'                             => Auth()->user()->idnumber,
                'created_at'                            => Carbon::now(),
                'updated_at'                            => Carbon::now(),
            ];

            $patientCourseInTheWardData = [
                'doctors_OrdersAction'     => $request->payload['doctors_OrdersAction'] ?? $courseInTheWard->doctors_OrdersAction,
                'createdBy'                => Auth()->user()->idnumber,
                'updatedBy'                => Auth()->user()->idnumber,
                'created_at'               => Carbon::now(),
                'updated_at'               => Carbon::now(),
            ];

            $patientPhysicalExamtionCVSData = [
                'essentially_Normal'        => $request->payload['essentially_Normal'] ?? $physicalExamtionCVS->essentially_Normal,
                'irregular_Rhythm'          => $request->payload['irregular_Rhythm'] ?? $physicalExamtionCVS->irregular_Rhythm,
                'displaced_Apex_Beat'       => $request->payload['displaced_Apex_Beat'] ?? $physicalExamtionCVS->displaced_Apex_Beat,
                'muffled_Heart_Sounds'      => $request->payload['muffled_Heart_Sounds'] ?? $physicalExamtionCVS->muffled_Heart_Sounds,
                'heaves_AndOR_Thrills'      => $request->payload['heaves_AndOR_Thrills'] ?? $physicalExamtionCVS->heaves_AndOR_Thrills,
                'murmurs'                   => $request->payload['murmurs'] ?? $physicalExamtionCVS->murmurs,
                'pericardial_Bulge'         => $request->payload['pericardial_Bulge'] ?? $physicalExamtionCVS->pericardial_Bulge,
                'others_Description'        => $request->payload['others_Description'] ?? $physicalExamtionCVS->others_Description,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? $physicalExamtionGeneralSurvey->awake_And_Alert,
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? $physicalExamtionGeneralSurvey->altered_Sensorium,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientPhysicalExamtionHEENTData = [
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? $physicalExamtionHEENT->essentially_Normal,
                'icteric_Sclerae'               => $request->payload['icteric_Sclerae'] ?? $physicalExamtionHEENT->icteric_Sclerae,
                'abnormal_Pupillary_Reaction'   => $request->payload['abnormal_Pupillary_Reaction'] ?? $physicalExamtionHEENT->abnormal_Pupillary_Reaction,
                'pale_Conjunctive'              => $request->payload['pale_Conjunctive'] ?? $physicalExamtionHEENT->pale_Conjunctive,
                'cervical_Lympadenopathy'       => $request->payload['cervical_Lympadenopathy'] ?? $physicalExamtionHEENT->cervical_Lympadenopathy,
                'sunken_Eyeballs'               => $request->payload['sunken_Eyeballs'] ?? $physicalExamtionHEENT->sunken_Eyeballs,
                'dry_Mucous_Membrane'           => $request->payload['dry_Mucous_Membrane'] ?? $physicalExamtionHEENT->dry_Mucous_Membrane,
                'sunken_Fontanelle'             => $request->payload['sunken_Fontanelle'] ?? $physicalExamtionHEENT->sunken_Fontanelle,
                'others_description'            => $request->payload['others_description'] ?? $physicalExamtionHEENT->others_description,
                'createdBy'                     => Auth()->user()->idnumber,
                'updatedBy'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalGUIEData = [
                'essentially_Normal'                => $request->payload['essentially_Normal'] ?? $physicalGUIE->essentially_Normal,
                'blood_StainedIn_Exam_Finger'       => $request->payload['blood_StainedIn_Exam_Finger'] ?? $physicalGUIE->blood_StainedIn_Exam_Finger,
                'cervical_Dilatation'               => $request->payload['cervical_Dilatation'] ?? $physicalGUIE->cervical_Dilatation,
                'presence_Of_AbnormalDischarge'     => $request->payload['presence_Of_AbnormalDischarge'] ?? $physicalGUIE->presence_Of_AbnormalDischarge,
                'others_Description'                => $request->payload['others_Description'] ?? $physicalGUIE->others_Description,
                'createdBy'                         => Auth()->user()->idnumber,
                'updatedBy'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
                'updated_at'                        => Carbon::now(),
            ];

            $patientPhysicalNeuroExamData = [
                'essentially_Normal'            => $request->payload['essentially_Normal'] ?? $physicalNeuroExam->essentially_Normal,
                'abnormal_Reflexes'             => $request->payload['abnormal_Reflexes'] ?? $physicalNeuroExam->abnormal_Reflexes,
                'abormal_Gait'                  => $request->payload['abormal_Gait'] ?? $physicalNeuroExam->abormal_Gait,
                'poor_Altered_Memory'           => $request->payload['poor_Altered_Memory'] ?? $physicalNeuroExam->poor_Altered_Memory,
                'abnormal_Position_Sense'       => $request->payload['abnormal_Position_Sense'] ?? $physicalNeuroExam->abnormal_Position_Sense,
                'poor_Muscle_Tone_Strength'     => $request->payload['poor_Muscle_Tone_Strength'] ?? $physicalNeuroExam->poor_Muscle_Tone_Strength,
                'abnormal_Decreased_Sensation'  => $request->payload['abnormal_Decreased_Sensation'] ?? $physicalNeuroExam->abnormal_Decreased_Sensation,
                'poor_Coordination'             => $request->payload['poor_Coordination'] ?? $physicalNeuroExam->poor_Coordination,
                'createdBy'                     => Auth()->user()->idnumber,
                'updatedBy'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updated_at'                    => Carbon::now(),
            ];

            $patientPhysicalSkinExtremitiesData = [
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
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];
            
            $patientOBGYNHistory = [
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
                'followUp_Prenatal_DateVisit_8th'                       => $request->payload['followUp_Prenatal_DateVisit_8th'] ?? $OBGYNHistory->followUp_Prenatal_ConsultationNo_8th,
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
                'createdBy'                                             => Auth()->user()->idnumber,
                'updatedBy'                                             => Auth()->user()->idnumber,
                'created_at'                                            => Carbon::now(),
                'updated_at'                                            => Carbon::now(),
            ];

            $patientPregnancyHistoryData = [
                'pregnancyNumber'   => $registry_id,
                'outcome'           => $request->payload['outcome'] ?? $pregnancyHistory->outcome,
                'deliveryDate'      => $request->payload['deliveryDate'] ?? $pregnancyHistory->deliveryDate,
                'complications'     => $request->payload['complications'] ?? $pregnancyHistory->complications,
                'createdBy'         => Auth()->user()->idnumber,
                'updatedBy'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditions = [
                'conditionName'     => $registry_id,
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? $gynecologicalConditions->diagnosisDate,
                'createdBy'         => Auth()->user()->idnumber,
                'updatedBy'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            $patientMedicationsData = [
                'item_Id'               => $request->payload['item_Id'] ?? $medications->item_Id,
                'drug_Description'      => $request->payload['drug_Description'] ?? $medications->drug_Description,
                'dosage'                => $request->payload['dosage'] ?? $medications->dosage,
                'reason_For_Use'        => $request->payload['reason_For_Use'] ?? $medications->reason_For_Use,
                'adverse_Side_Effect'   => $request->payload['adverse_Side_Effect'] ?? $medications->adverse_Side_Effect,
                'hospital'              => $request->payload['hospital'] ?? $medications->hospital,
                'isPrescribed'          => $request->payload['isPrescribed'] ?? $medications->isPrescribed,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            // $patientPrivilegedCard = [
            //     'patient_Id'            => $patient_id,
            //     'card_number'           => $request->payload['card_number'] ?? $privilegedCard->card_number,
            //     'card_Type_Id'          => $request->payload['card_Type_Id'] ?? $privilegedCard->card_Type_Id,
            //     'card_BenefitLevel'     => $request->payload['card_BenefitLevel'] ?? $privilegedCard->card_BenefitLevel,
            //     'card_PIN'              => $request->payload['card_PIN'] ?? $privilegedCard->card_PIN,
            //     'card_Bardcode'         => $request->payload['card_Bardcode'] ?? $privilegedCard->card_Bardcode,
            //     'card_RFID'             => $request->payload['card_RFID'] ?? $privilegedCard->card_RFID,
            //     'card_Balance'          => $request->payload['card_Balance'] ?? $privilegedCard->card_Balance,
            //     'issued_Date'           => $request->payload['issued_Date'] ?? $privilegedCard->issued_Date,
            //     'expiry_Date'           => $request->payload['expiry_Date'] ?? $privilegedCard->expiry_Date,
            //     'points_Earned'         => $request->payload['points_Earned'] ?? $privilegedCard->points_Earned,
            //     'points_Transferred'    => $request->payload['points_Transferred'] ?? $privilegedCard->points_Transferred,
            //     'points_Redeemed'       => $request->payload['points_Redeemed'] ?? $privilegedCard->points_Redeemed,
            //     'points_Forfeited'      => $request->payload['points_Forfeited'] ?? $privilegedCard->points_Forfeited,
            //     'card_Status'           => $request->payload['card_Status'] ?? $privilegedCard->card_Status,
            //     'createdBy'             => Auth()->user()->idnumber,
            //     'updatedBy'             => Auth()->user()->idnumber,
            //     'created_at'            => Carbon::now(),
            //     'updated_at'            => Carbon::now(),
            // ];

            $patientDischargeInstructions = [
                'branch_Id'                         => $request->payload['branch_Id'] ?? $dischargeInstructions->branch_Id,
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
                'createdBy'                         => Auth()->user()->idnumber,
                'updatedBy'                         => Auth()->user()->idnumber,
                'created_at'                        => Carbon::now(),
                'updated_at'                        => Carbon::now(),
            ];

            $patientDischargeMedications = [
                'Item_Id'               => $request->payload['Item_Id'] ?? $dischargeMedications->Item_Id,
                'medication_Name'       => $request->payload['medication_Name'] ?? $dischargeMedications->medication_Name,
                'medication_Type'       => $request->payload['medication_Type'] ?? $dischargeMedications->medication_Type,
                'dosage'                => $request->payload['dosage'] ?? $dischargeMedications->dosage,
                'frequency'             => $request->payload['frequency'] ?? $dischargeMedications->frequency,
                'purpose'               => $request->payload['purpose'] ?? $dischargeMedications->purpose,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientDischargeFollowUpTreatment = [
                'treatment_Description' => $request->payload['treatment_Description'] ?? $dischargeFollowUpTreatment->treatment_Description,
                'treatment_Date'        => $request->payload['treatment_Date'] ?? $dischargeFollowUpTreatment->treatment_Date,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? $dischargeFollowUpTreatment->doctor_Id,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? $dischargeFollowUpTreatment->doctor_Name,
                'notes'                 => $request->payload['notes'] ?? $dischargeFollowUpTreatment->notes,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientDischargeFollowUpLaboratories = [
                'item_Id'           => $request->payload['item_Id'] ?? $dischargeFollowUpLaboratories->item_Id,
                'test_Name'         => $request->payload['test_Name'] ?? $dischargeFollowUpLaboratories->test_Name,
                'test_DateTime'     => $request->payload['test_DateTime'] ?? $dischargeFollowUpLaboratories->test_DateTime,
                'notes'             => $request->payload['notes'] ?? $dischargeFollowUpLaboratories->notes,
                'createdBy'         => Auth()->user()->idnumber,
                'updatedBy'         => Auth()->user()->idnumber,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            $patientDischargeDoctorsFollowUp = [
                'doctor_Id'             => $request->payload['doctor_Id'] ?? $dischargeDoctorsFollowUp->doctor_Id,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? $dischargeDoctorsFollowUp->doctor_Name,
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? $dischargeDoctorsFollowUp->doctor_Specialization,
                'schedule_Date'         => $request->payload['schedule_Date'] ?? $dischargeDoctorsFollowUp->schedule_Date,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientHistoryData = [
                'branch_Id'                                 => $request->payload['branch_Id'] ?? 1,
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
                'description'                   => $request->payload['description'] ?? $patientMedicalProcedure->description,
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? $patientMedicalProcedure->date_Of_Procedure,
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? $patientMedicalProcedure->performing_Doctor_Id,
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? $patientMedicalProcedure->performing_Doctor_Fullname,
                'createdBy'                     => Auth()->user()->idnumber,
                'updatedBy'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updated_at'                    => Carbon::now(),
            ];

            $patientVitalSignsData = [
                'branch_Id'                 => 1,      
                'transDate'                 => $today,
                'bloodPressureSystolic'     => isset($request->payload['bloodPressureSystolic']) ? (int)$request->payload['bloodPressureSystolic'] :  $patientVitalSign->bloodPressureSystolic,
                'bloodPressureDiastolic'    => isset($request->payload['bloodPressureDiastolic']) ? (int)$request->payload['bloodPressureDiastolic'] : $patientVitalSign->bloodPressureDiastolic,
                'temperature'               => isset($request->payload['temperatue']) ? (int)$request->payload['temperatue'] : $patientVitalSign->temperature,
                'pulseRate'                 => isset($request->payload['pulseRate']) ? (int)$request->payload['pulseRate'] : $patientVitalSign->pulseRate,
                'respiratoryRate'           => isset($request->payload['respiratoryRate']) ? (int)$request->payload['respiratoryRate'] : $patientVitalSign->respiratoryRate,
                'oxygenSaturation'          => isset($request->payload['oxygenSaturation']) ? (float)$request->payload['oxygenSaturation'] : $patientVitalSign->oxygenSaturation,
                'createdBy'                 => Auth()->user()->idnumber,
                'updatedBy'                 => Auth()->user()->idnumber,
                'created_at'                => Carbon::now(),
                'updated_at'                => Carbon::now(),
            ];

      
            $patientRegistryData = [
            'branch_Id'                                 => $request->payload['branch_Id'] ?? $patientRegistry->branch_Id,
            'er_Case_No'                                => $request->payload['er_Case_No'] ?? $patientRegistry->er_Case_No,
            'register_source'                           => $request->payload['register_source'] ?? $patientRegistry->register_Source,
            'register_Casetype'                         => $request->payload['register_Casetype'] ?? $patientRegistry->register_Casetype,
            'register_Link_Case_No'                     => $request->payload['register_Link_Case_No'] ?? $patientRegistry->register_Link_Case_No,
            'register_Case_No_Consolidate'              => $request->payload['register_Case_No_Consolidate'] ?? $patientRegistry->register_Case_No_Consolidate,
            'patient_Age'                               => $request->payload['age'] ?? $patientRegistry->patient_Age,
            'er_Bedno'                                  => $request->payload['er_Bedno'] ?? $patientRegistry->er_Bedno,
            'room_Code'                                 => $request->payload['room_Code'] ?? $patientRegistry->room_Code,
            'room_Rate'                                 => $request->payload['room_Rate'] ?? $patientRegistry->room_Rate,
            'mscAccount_Type'                           => $request->payload['mscAccount_Type'] ?? $patientRegistry->mscAccount_Type,
            'mscAccount_Discount_Id'                    => $request->payload['mscAccount_Discount_Id'] ?? $patientRegistry->mscAccount_Discount_Id,
            'mscAccount_Trans_Types'                    => $request->payload['mscAccount_Trans_Types'] ?? $patientRegistry->mscAccount_Trans_Types, 
            'mscAdmission_Type_Id'                      => $request->payload['mscAdmission_Type_Id'] ?? $patientRegistry->mscAdmission_Type_Id,
            'mscPatient_Category'                       => $request->payload['mscPatient_Category'] ?? $patientRegistry->mscPatient_Category,
            'mscPrice_Groups'                           => $request->payload['mscPrice_Groups'] ?? $patientRegistry->mscPrice_Groups,
            'mscPrice_Schemes'                          => $request->payload['mscPrice_Schemes'] ?? $patientRegistry->mscPrice_Schemes,
            'mscService_Type'                           => $request->payload['mscService_Type'] ?? $patientRegistry->mscService_Type,
            'mscService_Type2'                          => $request->payload['mscService_Type2'] ?? $patientRegistry->mscService_Type2,
            'mscDiet_Meal_Id'                           => $request->payload['mscDiet_Meal_Id'] ?? $patientRegistry->mscDiet_Meal_Id,
            'mscDisposition_Id'                         => $request->payload['mscDisposition_Id'] ?? $patientRegistry->mscDisposition_Id,
            'mscTriage_level_Id'                        => $request->payload['mscTriage_level_Id'] ?? $patientRegistry->mscTriage_level_Id,
            'mscCase_Result_Id'                         => $request->payload['mscCase_Result_Id'] ?? $patientRegistry->mscCase_Result_Id,
            'mscCase_Indicators_Id'                     => $request->payload['mscCase_Indicators_Id'] ?? $patientRegistry->mscCase_Indicators_Id,
            'mscPrivileged_Card_Id'                     => $request->payload['mscPrivileged_Card_Id'] ?? $patientRegistry->mscPrivileged_Card_Id,
            'mscBroughtBy_Relationship_Id'              => $request->payload['mscBroughtBy_Relationship_Id'] ?? $patientRegistry->mscBroughtBy_Relationship_Id,
            'queue_Number'                              => $request->payload['queue_Number'] ?? $patientRegistry->queue_Number,
            'arrived_Date'                              => Carbon::now(),
            'registry_Userid'                           => Auth()->user()->idnumber,
            'registry_Date'                             => Carbon::now(),
            'registry_Status'                           => $request->payload['registry_Status'] ?? $patientRegistry->registry_Status,
            'registry_Hostname'                         => $request->payload['registry_Hostname'] ?? $patientRegistry->registry_Hostname,
            'discharged_Userid'                         => $request->payload['discharged_Userid'] ?? $patientRegistry->discharged_Userid,
            'discharged_Date'                           => $request->payload['discharged_Date'] ?? $patientRegistry->discharged_Date,
            'discharged_Hostname'                       => $request->payload['discharged_Hostname'] ?? $patientRegistry->discharged_Hostname,
            'billed_Userid'                             => $request->payload['billed_Userid'] ?? $patientRegistry->billed_Userid,
            'billed_Date'                               => $request->payload['billed_Date'] ?? $patientRegistry->billed_Date,
            'billed_Remarks'                            => $request->payload['billed_Remarks'] ?? $patientRegistry->billed_Remarks,
            'billed_Hostname'                           => $request->payload['billed_Hostname'] ?? $patientRegistry->billed_Hostname,
            'mgh_Userid'                                => $request->payload['mgh_Userid'] ?? $patientRegistry->mgh_Userid,
            'mgh_Datetime'                              => $request->payload['mgh_Datetime'] ?? $patientRegistry->mgh_Datetime,
            'mgh_Hostname'                              => $request->payload['mgh_Hostname'] ?? $patientRegistry->mgh_Hostname,
            'untag_Mgh_Userid'                          => $request->payload['untag_Mgh_Userid'] ?? $patientRegistry->untag_Mgh_Userid,
            'untag_Mgh_Datetime'                        => $request->payload['untag_Mgh_Datetime'] ?? $patientRegistry->untag_Mgh_Datetime,
            'untag_Mgh_Hostname'                        => $request->payload['untag_Mgh_Hostname'] ?? $patientRegistry->untag_Mgh_Hostname,
            'isHoldReg'                                 => $request->payload['isHoldReg'] ?? false,
            'hold_Userid'                               => $request->payload['hold_Userid'] ?? $patientRegistry->hold_Userid,
            'hold_No'                                   => $request->payload['hold_No'] ?? $patientRegistry->hold_No,
            'hold_Date'                                 => $request->payload['hold_Date'] ?? $patientRegistry->hold_Date,
            'hold_Remarks'                              => $request->payload['hold_Remarks'] ?? $patientRegistry->hold_Remarks,
            'hold_Hostname'                             => $request->payload['hold_Hostname'] ?? $patientRegistry->hold_Hostname,
            'isRevoked'                                 => $request->payload['isRevoked'] ?? false,
            'revokedBy'                                 => $request->payload['revokedBy'] ?? $patientRegistry->revokedBy,
            'revoked_Date'                              => $request->payload['revoked_Date'] ?? $patientRegistry->revoked_Date,
            'revoked_Remarks'                           => $request->payload['revoked_Remarks'] ?? $patientRegistry->revoked_Remarks,
            'revoked_Hostname'                          => $request->payload['revoked_Hostname'] ?? $patientRegistry->revoked_Hostname,
            'dischargeNotice_Userid'                    => $request->payload['dischargeNotice_Userid'] ?? $patientRegistry->dischargeNotice_Userid,
            'dischargeNotice_Date'                      => $request->payload['dischargeNotice_Date'] ?? $patientRegistry->dischargeNotice_Date,
            'dischargeNotice_Hostname'                  => $request->payload['dischargeNotice_Hostname'] ?? $patientRegistry->dischargeNotice_Hostname,
            'hbps_PrintedBy'                            => $request->payload['hbps_PrintedBy'] ?? $patientRegistry->hbps_PrintedBy,
            'hbps_Date'                                 => $request->payload['hbps_Date'] ?? $patientRegistry->hbps_Date,
            'hbps_Hostname'                             => $request->payload['hbps_Hostname'] ?? $patientRegistry->hbps_Hostname,
            'informant_Lastname'                        => $request->payload['informant_Lastname'] ?? $patientRegistry->informant_Lastname,
            'informant_Firstname'                       => $request->payload['informant_Firstname'] ?? $patientRegistry->informant_Firstname,
            'informant_Middlename'                      => $request->payload['informant_Middlename'] ?? $patientRegistry->informant_Middlename,
            'informant_Suffix'                          => $request->payload['informant_Suffix'] ?? $patientRegistry->informant_Suffix,
            'informant_Address'                         => $request->payload['informant_Address'] ?? $patientRegistry->informant_Address,
            'informant_Relation_id'                     => $request->payload['informant_Relation_id'] ?? $patientRegistry->informant_Relation_id,
            'guarantor_Id'                              => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patientRegistry->guarantor_Id,
            'guarantor_Name'                            => $request->payload['selectedGuarantor'][0]['guarantor_Name'] ?? $patientRegistry->guarantor_Name,
            'guarantor_Approval_code'                   => $request->payload['selectedGuarantor'][0]['guarantor_Approval_code'] ?? $patientRegistry->guarantor_Approval_code,
            'guarantor_Approval_no'                     => $request->payload['selectedGuarantor'][0]['guarantor_Approval_no'] ?? $patientRegistry->guarantor_Approval_no,
            'guarantor_Approval_date'                   => $request->payload['selectedGuarantor'][0]['guarantor_Approval_date'] ?? $patientRegistry->guarantor_Approval_date,
            'guarantor_Validity_date'                   => $request->payload['selectedGuarantor'][0]['guarantor_Validity_date'] ?? $patientRegistry->guarantor_Validity_date,
            'guarantor_Approval_remarks'                => $request->payload['guarantor_Approval_remarks'] ?? $patientRegistry->guarantor_Approval_remarks,
            'isWithCreditLimit'                         => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
            'guarantor_Credit_Limit'                    => $request->payload['selectedGuarantor'][0]['guarantor_Credit_Limit'] ?? $patientRegistry->guarantor_Credit_Limit,
            'isWithMultiple_Gurantor'                   => $request->payload['isWithMultiple_Gurantor'] ?? false,
            'gurantor_Mutiple_TotalCreditLimit'         => $request->payload['gurantor_Mutiple_TotalCreditLimit'] ?? false,
            'isWithPhilHealth'                          => $request->payload['isWithPhilHealth'] ?? false,
            'mscPHIC_Membership_Type_id'                => $request->payload['mscPHIC_Membership_Type_id'] ?? $patientRegistry->mscPHIC_Membership_Type_id,
            'philhealth_Number'                         => $request->payload['philhealth_Number'] ?? $patientRegistry->philhealth_Number,
            'isWithMedicalPackage'                      => $request->payload['isWithMedicalPackage'] ?? false,
            'medical_Package_Id'                        => $request->payload['medical_Package_Id'] ?? $patientRegistry->medical_Package_Id,
            'medical_Package_Name'                      => $request->payload['medical_Package_Name'] ?? $patientRegistry->medical_Package_Name,
            'medical_Package_Amount'                    => $request->payload['medical_Package_Amount'] ?? $patientRegistry->medical_Package_Amount,
            'chief_Complaint_Description'               => $request->payload['chief_Complaint_Description'] ?? $patientRegistry->chief_Complaint_Description,
            'impression'                                => $request->payload['impression'] ?? $patientRegistry->impression,
            'admitting_Diagnosis'                       => $request->payload['admitting_Diagnosis'] ?? $patientRegistry->admitting_Diagnosis,
            'discharge_Diagnosis'                       => $request->payload['discharge_Diagnosis'] ?? $patientRegistry->discharge_Diagnosis,
            'preOperative_Diagnosis'                    => $request->payload['preOperative_Diagnosis'] ?? $patientRegistry->preOperative_Diagnosis,
            'postOperative_Diagnosis'                   => $request->payload['postOperative_Diagnosis'] ?? $patientRegistry->postOperative_Diagnosis,
            'surgical_Procedure'                        => $request->payload['surgical_Procedure'] ?? $patientRegistry->surgical_Procedure,
            'triageNotes'                               => $request->payload['triageNotes'] ?? $patientRegistry->triageNotes,
            'triageDate'                                => $request->payload['triageDate'] ?? $patientRegistry->triageDate,
            'isCriticallyIll'                           => $request->payload['isCriticallyIll'] ?? false,
            'illness_Type'                              => $request->payload['illness_Type'] ?? $patientRegistry->illness_Type,
            'attending_Doctor'                          => $request->payload['selectedConsultant']['attending_Doctor'] ?? $patientRegistry->attending_Doctor,
            'attending_Doctor_fullname'                 => $request->payload['selectedConsultant']['attending_Doctor_fullname'] ?? $patientRegistry->attending_Doctor_fullname,
            'bmi'                                       => $request->payload['bmi'] ?? $patientRegistry->bmi,
            'weight'                                    => $request->payload['weight'] ?? $patientRegistry->weight,
            'weightUnit'                                => $request->payload['weightUnit'] ?? $patientRegistry->weightUnit,
            'height'                                    => $request->payload['height'] ?? $patientRegistry->height,
            'heightUnit'                                => $request->payload['heightUnit'] ?? $patientRegistry->heightUnit,
            'bloodPressureSystolic'                     => $request->payload['bloodPressureSystolic'] ?? $patientRegistry->bloodPressureSystolic,
            'bloodPressureDiastolic'                    => $request->payload['bloodPressureDiastolic'] ?? $patientRegistry->bloodPressureDiastolic,
            'temperatute'                               => $request->payload['temperatute'] ?? $patientRegistry->temperatute,
            'pulseRate'                                 => $request->payload['pulseRate'] ?? $patientRegistry->pulseRate,
            'respiratoryRate'                           => $request->payload['respiratoryRate'] ?? $patientRegistry->respiratoryRate,
            'oxygenSaturation'                          => $request->payload['oxygenSaturation'] ?? $patientRegistry->oxygenSaturation,
            'isHemodialysis'                            => $isHemodialysis,
            'isPeritoneal'                              => $isPeritoneal,
            'isLINAC'                                   => $isLINAC,
            'isCOBALT'                                  => $isCOBALT,
            'isBloodTrans'                              => $isBloodTrans,
            'isChemotherapy'                            => $isChemotherapy,
            'isBrachytherapy'                           => $isBrachytherapy,
            'isDebridement'                             => $isDebridement,
            'isTBDots'                                  => $isTBDots,
            'isPAD'                                     => $isPAD,
            'isRadioTherapy'                            => $isRadioTherapy,
            'typeOfBirth_id'                            => $request->payload['typeOfBirth_id'] ?? $patientRegistry->typeOfBirth_id,
            'isWithBaby'                                => $request->payload['isWithBaby'] ?? $patientRegistry->isWithBaby,
            'isRoomIn'                                  => $request->payload['isRoomIn'] ?? $patientRegistry->isRoomIn,
            'birthDate'                                 => $request->payload['birthDate'] ?? $patientRegistry->birthDate,
            'birthTime'                                 => $request->payload['birthTime'] ?? $patientRegistry->birthTime,
            'newborn_Status_Id'                         => $request->payload['newborn_Status_Id'] ?? $patientRegistry->newborn_Status_Id,
            'mother_Case_No'                            => $request->payload['mother_Case_No'] ?? $patientRegistry->mother_Case_No,
            'isDiedLess48Hours'                         => $request->payload['isDiedLess48Hours'] ?? $patientRegistry->isDiedLess48Hours,
            'isDeadOnArrival'                           => $request->payload['isDeadOnArrival'] ?? $patientRegistry->isDeadOnArrival,
            'isAutopsy'                                 => $request->payload['isAutopsy'] ?? $patientRegistry->isAutopsy,
            'typeOfDeath_id'                            => $request->payload['typeOfDeath_id'] ?? $patientRegistry->typeOfDeath_id,
            'dateOfDeath'                               => $request->payload['dateOfDeath'] ?? $patientRegistry->dateOfDeath,
            'timeOfDeath'                               => $request->payload['timeOfDeath'] ?? $patientRegistry->timeOfDeath,
            'barcode_Image'                             => $request->payload['barcode_Image'] ?? $patientRegistry->barcode_Image,
            'barcode_Code_Id'                           => $request->payload['barcode_Code_Id'] ?? $patientRegistry->barcode_Code_Id,
            'barcode_Code_String'                       => $request->payload['barcode_Code_String'] ?? $patientRegistry->barcode_Code_String,
            'isreferredFrom'                            => $request->payload['isreferredFrom'] ?? false,
            'referred_From_HCI'                         => $request->payload['referred_From_HCI'] ?? $patientRegistry->referred_From_HCI,
            'referred_From_HCI_address'                 => $request->payload['FromHCIAddress'] ?? $patientRegistry->referred_From_HCI_address,
            'referred_From_HCI_code'                    => $request->payload['referred_From_HCI_code'] ?? $patientRegistry->referred_From_HCI_code,
            'referred_To_HCI'                           => $request->payload['referred_To_HCI'] ?? $patientRegistry->referred_To_HCI,
            'referred_To_HCI_code'                      => $request->payload['referred_To_HCI_code'] ?? $patientRegistry->referred_To_HCI_code,
            'referred_To_HCI_address'                   => $request->payload['ToHCIAddress'] ?? $patientRegistry->referred_To_HCI_address,
            'referring_Doctor'                          => $request->payload['referring_Doctor'] ?? $patientRegistry->referring_Doctor,
            'referral_Reason'                           => $request->payload['referral_Reason'] ?? $patientRegistry->referral_Reason,
            'isWithConsent_DPA'                         => $request->payload['isWithConsent_DPA'] ?? $patientRegistry->isWithConsent_DPA,
            'isConfidentialPatient'                     => $request->payload['isConfidentialPatient'] ?? $patientRegistry->isConfidentialPatient,
            'isMedicoLegal'                             => $request->payload['isMedicoLegal'] ?? $patientRegistry->isMedicoLegal,
            'isFinalBill'                               => $request->payload['isFinalBill'] ?? $patientRegistry->isFinalBill,
            'isWithPromissoryNote'                      => $request->payload['isWithPromissoryNote'] ?? $patientRegistry->isWithPromissoryNote,
            'isFirstNotice'                             => $request->payload['isFirstNotice'] ?? $patientRegistry->isFirstNotice,
            'FirstNoteDate'                             => $request->payload['FirstNoteDate'] ?? $patientRegistry->FirstNoteDate,
            'isSecondNotice'                            => $request->payload['isSecondNotice'] ?? $patientRegistry->isSecondNotice,
            'SecondNoticeDate'                          => $request->payload['SecondNoticeDate'] ?? $patientRegistry->SecondNoticeDate,
            'isFinalNotice'                             => $request->payload['isFinalNotice'] ?? $patientRegistry->isFinalNotice,
            'FinalNoticeDate'                           => $request->payload['FinalNoticeDate'] ?? $patientRegistry->FinalNoticeDate,
            'isOpenLateCharges'                         => $request->payload['isOpenLateCharges'] ?? $patientRegistry->isOpenLateCharges,
            'isBadDebt'                                 => $request->payload['isBadDebt'] ?? $patientRegistry->isBadDebt,
            'registry_Remarks'                          => $request->payload['registry_Remarks'] ?? $patientRegistry->registry_Remarks,
            'medsys_map_idnum'                          => $request->payload['medsys_map_idnum'] ?? $patientRegistry->medsys_map_idnum,
            'createdBy'                                 => Auth()->user()->idnumber,
            'updatedBy'                                 => Auth()->user()->idnumber,
            'created_at'                                => Carbon::now(),
            'updated_at'                                => Carbon::now(),           
            ];
    

            $patientImmunizationsData = [
                'branch_id'             => 1,     
                'vaccine_Id'            => $request->payload['vaccine_Id'] ?? $patientImmunization->vaccine_Id,
                'administration_Date'   => $request->payload['administration_Date'] ?? $patientImmunization->administration_Date,
                'dose'                  => $request->payload['dose'] ?? $patientImmunization->dose,
                'site'                  => $request->payload['site'] ?? $patientImmunization->site,
                'administrator_Name'    => $request->payload['administrator_Name'] ?? $patientImmunization->administrator_Name,
                'Notes'                 => $request->payload['Notes'] ?? $patientImmunization->Notes,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];

            $patientAdministeredMedicineData = [
                'item_Id'               => $request->payload['item_Id'] ?? $patientAdministeredMedicine->item_Id,
                'quantity'              => $request->payload['quantity'] ?? $patientAdministeredMedicine->quantity,
                'administered_Date'     => $request->payload['administered_Date'] ?? $patientAdministeredMedicine->administered_Date,
                'administered_By'       => $request->payload['administered_By'] ?? $patientAdministeredMedicine->administered_By,
                'reference_num'         => $request->payload['reference_num'] ?? $patientAdministeredMedicine->reference_num,
                'transaction_num'       => $request->payload['transaction_num'] ?? $patientAdministeredMedicine->transaction_num,
                'createdBy'             => Auth()->user()->idnumber,
                'updatedBy'             => Auth()->user()->idnumber,
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];
            $patient->updateOrCreate($checkPatient, $patientData);
            if($existingRegistry) {
                $pastImmunization->whereDate('created_at', $today)->update($patientPastImmunizationData);
                $pastMedicalHistory->whereDate('created_at', $today)->update($patientPastMedicalHistoryData);
                $pastMedicalProcedure->whereDate('created_at', $today)->update($pastientPastMedicalProcedureData);
                $pastAllergyHistory->whereDate('created_at', $today)->update($pastientPastAllergyHistoryData);
                $pastCauseOfAllergy->update($pastientPastCauseOfAllergyData);
                $pastSymtomsOfAllergy->update($pastientPastSymptomsOfAllergyData);
                $pastBadHabits->whereDate('created_at', $today)->update($patientPastBadHabitsData);
                
                $patientRegistry->whereDate('created_at', $today)->update($patientRegistryData);
                $patientHistory->whereDate('created_at', $today)->update($patientHistoryData);
                $patientMedicalProcedure->whereDate('created_at', $today)->update($patientMedicalProcedureData);
                $patientVitalSign->whereDate('created_at', $today)->update($patientVitalSignsData);
                $patientImmunization->whereDate('created_at', $today)->update($patientImmunizationsData);
                $patientAdministeredMedicine->whereDate('created_at', $today)->update($patientAdministeredMedicineData);

                $OBGYNHistory->whereDate('created_at', $today)->update($patientOBGYNHistory);
                $pregnancyHistory->whereDate('created_at', $today)->update($patientPregnancyHistoryData);
                $gynecologicalConditions->whereDate('created_at', $today)->update($patientGynecologicalConditions);

                $allergy->whereDate('created_at', $today)->update($patientAllergyData);
                // updateIfNotNull($causeOfAllergy, $patientCauseOfAllergyData);
                // updateIfNotNull($symptomsOfAllergy, $patientSymptomsOfAllergyData);
                // $causeOfAllergy->update($patientCauseAllergyData);
                // $symptomsOfAllergy->whereDate('created_at', $today)->update($patientSymptomsOfAllergy);
       
                $badHabits->whereDate('created_at', $today)->update($patientBadHabitsData);
                $drugUsedForAllergy->whereDate('created_at', $today)->update($patientDrugUsedForAllergyData);
                $patientDoctors->whereDate('created_at', $today)->update($patientDoctorsData);
                $physicalAbdomen->whereDate('created_at', $today)->update($patientPhysicalAbdomenData);
                $pertinentSignAndSymptoms->whereDate('created_at', $today)->update($patientPertinentSignAndSymptomsData);
                $physicalExamtionChestLungs->whereDate('created_at', $today)->update($patientPhysicalExamtionChestLungsData);
                $courseInTheWard->whereDate('created_at', $today)->update($patientCourseInTheWardData);
                $physicalExamtionCVS->whereDate('created_at', $today)->update($patientPhysicalExamtionCVSData);
                $physicalExamtionGeneralSurvey->whereDate('created_at', $today)->update($patientPhysicalExamtionGeneralSurveyData);
                $physicalExamtionHEENT->whereDate('created_at', $today)->update($patientPhysicalExamtionHEENTData);
                $physicalGUIE->whereDate('created_at', $today)->update($patientPhysicalGUIEData);
                $physicalNeuroExam->whereDate('created_at', $today)->update($patientPhysicalNeuroExamData);
                $physicalSkinExtremities->whereDate('created_at', $today)->update($patientPhysicalSkinExtremitiesData);
                $medications->whereDate('created_at', $today)->update($patientMedicationsData);

                $dischargeInstructions->whereDate('created_at', $today)->update($patientDischargeInstructions);
                $dischargeMedications->whereDate('created_at', $today)->update($patientDischargeMedications);
                $dischargeFollowUpTreatment->whereDate('created_at', $today)->update($patientDischargeFollowUpTreatment);
                $dischargeFollowUpLaboratories->whereDate('created_at', $today)->update($patientDischargeFollowUpLaboratories);
                $dischargeDoctorsFollowUp->whereDate('created_at', $today)->update($patientDischargeDoctorsFollowUp);

            } else {
                $pastImmunization->create( $patientPastImmunizationData);
                $pastMedicalHistory->create( $patientPastMedicalHistoryData);
                $pastMedicalProcedure->create( $pastientPastMedicalProcedureData);
                $pastAllergyHistory->create( $pastientPastAllergyHistoryData);
                $pastCauseOfAllergy->create( $pastientPastCauseOfAllergyData);
                $pastSymtomsOfAllergy->create( $pastientPastSymptomsOfAllergyData);
                $pastBadHabits->create( $patientPastBadHabitsData);

                $patientRegistry->create($patientRegistryData);
                $patientHistory->create($patientHistoryData);
                $patientMedicalProcedure->create($patientMedicalProcedureData);
                $patientVitalSign->create($patientVitalSignsData);
                $patientImmunization->create($patientImmunizationsData);
                $patientAdministeredMedicine->create($patientAdministeredMedicineData);

                $newOBGYNdData = $OBGYNHistory->create($patientOBGYNHistory);
                $patientPregnancyHistoryData['OBGYNHistoryID'] = $newOBGYNdData->id;
                $patientGynecologicalConditions['OBGYNHistoryID'] = $newOBGYNdData->id;
                $pregnancyHistory->create($patientPregnancyHistoryData);
                $gynecologicalConditions->create($patientGynecologicalConditions);

                $newPatientAllergy = $allergy->create($patientAllergyData);
                $patientCauseAllergyData['allergies_Id'] = $newPatientAllergy->id;
                $patientSymptomsOfAllergyData['allergies_Id'] = $newPatientAllergy->id;
                $cause = $causeOfAllergy->create($patientCauseAllergyData);
                $symptomsOfAllergy->create($patientSymptomsOfAllergyData);

                $badHabits->create($patientBadHabitsData);
                $drugUsedForAllergy->create($patientDrugUsedForAllergyData);
                $patientDoctors->create($patientDoctorsData);
                $physicalAbdomen->create($patientPhysicalAbdomenData);
                $pertinentSignAndSymptoms->create($patientPertinentSignAndSymptomsData);
                $physicalExamtionChestLungs->create($patientPhysicalExamtionChestLungsData);
                $courseInTheWard->create($patientCourseInTheWardData);
                $physicalExamtionCVS->create($patientPhysicalExamtionCVSData);
                $physicalExamtionGeneralSurvey->create($patientPhysicalExamtionGeneralSurveyData);
                $physicalExamtionHEENT->create($patientPhysicalExamtionHEENTData);
                $physicalGUIE->create($patientPhysicalGUIEData);
                $physicalNeuroExam->create($patientPhysicalNeuroExamData);
                $physicalSkinExtremities->create($patientPhysicalSkinExtremitiesData);

                $medications->create($patientMedicationsData);

                $newDischargedInstructions = $dischargeInstructions->create($patientDischargeInstructions);
                $patientDischargeMedications['instruction_Id'] = $newDischargedInstructions->instruction_Id;
                $patientDischargeFollowUpTreatment['instruction_Id'] = $newDischargedInstructions->instruction_Id;
                $patientDischargeFollowUpLaboratories['instruction_Id'] = $newDischargedInstructions->instruction_Id;
                $patientDischargeDoctorsFollowUp['instruction_Id'] = $newDischargedInstructions->instruction_Id;
                $dischargeMedications->create($patientDischargeMedications);
                $dischargeFollowUpTreatment->create($patientDischargeFollowUpTreatment);
                $dischargeFollowUpLaboratories->create($patientDischargeFollowUpLaboratories);
                $dischargeDoctorsFollowUp->create($patientDischargeDoctorsFollowUp);
            }

            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Emergency data updated successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message'   => 'Failed to update Emergency data',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    public function getrevokedemergencypatient() {
        try {
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $today = Carbon::now()->format('Y-m-d');

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_trans_types', 5);
                $query->where('isRevoked', 1);
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
                'message' => 'Failed to get revoked Emergency data',
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
    

