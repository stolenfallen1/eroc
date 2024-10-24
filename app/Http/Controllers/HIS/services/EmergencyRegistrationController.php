<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\MedsysSeriesNo;
use App\Helpers\HIS\HISCentralSequences;
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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetIP;
use App\Helpers\HIS\UpdateIfNotNull;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Helpers\HIS\SysGlobalSetting;
class EmergencyRegistrationController extends Controller
{
    protected $check_is_allow_medsys;
    protected $isproduction;
    public function __construct() {
        $this->isproduction = true;
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    //
    public function index() {
        try {
            // $today = Carbon::now()->format('Y-m-d'); 
            $today = '2024-10-18'; 
            $data = Patient::query();

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5)  
                    ->where('isRevoked', 0)              
                    ->whereDate('registry_Date', $today); 
            });

            // $data->with([
            //     'sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries',
            //     'patientRegistry.allergies' => function ($query)use ($today) {
            //         $query->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy');
            //         $query->where('isDeleted', '!=', 1);
            //         $query->whereDate('created_at', $today);
            //     }
            // ]);

            if (Request()->has('keyword')) {
                $keyword = Request()->keyword;

                $data->where(function($subQuery) use ($keyword) {
                    $subQuery->where('lastname', 'LIKE', '%' . $keyword . '%')
                             ->orWhere('firstname', 'LIKE', '%' . $keyword . '%')
                             ->orWhere('patient_id', 'LIKE', '%' . $keyword . '%');
                });

            }

            $data->with([
                'sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries',
                'patientRegistry.allergies' => function ($query) use ($today) {
                    $query->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy');
                    $query->where('isDeleted', '!=', 1);
                    $query->whereDate('created_at', $today);
                }
            ]);

            $data->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get patients',
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
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            SystemSequence::where('code','MPID')->increment('seq_no');
            SystemSequence::where('code','MERN')->increment('seq_no');
            SystemSequence::where('code','MOPD')->increment('seq_no');
            SystemSequence::where('code','SERCN')->increment('seq_no');

            $sequence = SystemSequence::where('code', 'MPID')->select('seq_no', 'recent_generated')->first();
            $registry_sequence = SystemSequence::where('code', 'MERN')->select('seq_no', 'recent_generated')->first();
            $er_case_sequence = SystemSequence::where('code', 'SERCN')->select('seq_no', 'recent_generated')->first();

            if($this->check_is_allow_medsys) {

                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('ERNum');

                $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'ERNum', 'OPDId')->first();

                $patient_id     = $check_medsys_series_no->HospNum;
                $registry_id    = $check_medsys_series_no->OPDId;
                $er_Case_No     = $check_medsys_series_no->ERNum;
            
            } else {
            
                $patient_id             = $request->payload['patient_Id'] ?? intval($sequence->seq_no);
                $registry_id            = $request->payload['case_No'] ?? intval($registry_sequence->seq_no);
                $er_Case_No             = $request->payload['er_Case_No'] ?? intval($er_case_sequence->seq_no);
            }
            
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

            $currentTimestamp = Carbon::now();
            $existingPatient = Patient::where('lastname', $request->payload['lastname'])
                ->where('firstname', $request->payload['firstname'])
                ->first();
            
            if ($existingPatient):
                $patient_id = $existingPatient->patient_Id;
            else:
                $sequence->where('code', 'MPID')->update([
                    'recent_generated'  => $patient_id
                ]);

                $registry_sequence->where('code', 'MERN')->update([
                    'recent_generated'  => $registry_id
                ]);

                $registry_sequence->where('code', 'MOPD')->update([
                    'recent_generated'  => $registry_id
                ]);

                $er_case_sequence->where('code', 'SERCN')->update([
                    'recent_generated'  => $er_Case_No
                ]);
                
            endif;

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
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
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientPastMedicalHistoryData = [
                'patient_Id'                => $patient_id,
                'diagnose_Description'      => $request->payload['diagnose_Description'] ?? null,
                'diagnosis_Date'            => $request->payload['diagnosis_Date'] ?? null,
                'treament'                  => $request->payload['treament'] ?? null,
                'createdby'                 => $checkUser->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $pastientPastMedicalProcedureData =[
                'patient_Id'                => $patient_id,
                'description'               => $request->payload['description'] ?? null,
                'date_Of_Procedure'         => $request->payload['date_Of_Procedure'] ?? null,
                'createdby'                 => $checkUser->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientAdministeredMedicineData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'transactDate'          => Carbon::now(),
                'item_Id'               => $request->payload['item_Id'] ?? null,
                'quantity'              => $request->payload['quantity'] ?? null,
                'administered_Date'     => $request->payload['administered_Date'] ?? null,
                'administered_By'       => $request->payload['administered_By'] ?? null,
                'reference_num'         => $request->payload['reference_num'] ?? null,
                'transaction_num'       => $request->payload['transaction_num'] ?? null,
                'createdby'             => $checkUser->idnumber,
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
                'createdby'                                 => $checkUser->idnumber,
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
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now(),
            ];

            $patientMedicalProcedureData = [
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'description'                   => $request->payload['description'] ?? null,
                'date_Of_Procedure'             => $request->payload['date_Of_Procedure'] ?? null,
                'performing_Doctor_Id'          => $request->payload['performing_Doctor_Id'] ?? null,
                'performing_Doctor_Fullname'    => $request->payload['performing_Doctor_Fullname'] ?? null,
                'createdby'                     => $checkUser->idnumber,
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
                'createdby'                 => $checkUser->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientRegistryData = [
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
                'mscAccount_Trans_Types'                    => $request->payload['mscAccount_Trans_Types'] ?? 5, 
                'mscAdmission_Type_Id'                      => $request->payload['mscAdmission_Type_Id'] ?? null,
                'mscPatient_Category'                       => $request->payload['mscPatient_Category'] ?? 2,
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
                'registry_Userid'                           => $checkUser->idnumber,
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
                'guarantor_Name'                            => $request->payload['selectedGuarantor'][0]['guarantor_Name'] ?? 'Self Pay',
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
                'createdBy'                                 => $checkUser->idnumber,
                'created_at'                                => Carbon::now(),           
            ];    

            $patientBadHabitsData = [
                'patient_Id'    => $patient_id,
                'case_No'       => $registry_id,
                'description'   => $request->payload['description'] ?? null,
                'createdby'     => $checkUser->idnumber,
                'created_at'    => Carbon::now(),
            ];

            $patientPastBadHabitsData = [
                'patient_Id'    => $patient_id,
                'description'   => '',
                'createdby'     => $checkUser->idnumber,
                'created_at'    => Carbon::now(),
            ];

            // $patientDrugUsedForAllergyData = [
            //     'patient_Id'        => $patient_id,
            //     'case_No'           => $registry_id,
            //     'assessID'          => '',
            //     'allergy_Type_Id'   => '',
            //     'drug_Description'  => $request->payload['drug_Description'] ?? null,
            //     'createdby'         => $checkUser->idnumber,
            //     'created_at'        => Carbon::now(),
            // ];

            $patientDoctorsData = [
                'patient_Id'        => $patient_id,
                'case_No'           => $registry_id,
                'doctor_Id'         => $request->payload['doctor_Id'] ?? null,
                'doctors_Fullname'  => $request->payload['doctors_Fullname'] ?? null,
                'role_Id'           => $request->payload['role_Id'] ?? null,
                'createdby'         => $checkUser->idnumber,
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
                'createdby'                 => $checkUser->idnumber,
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
                'createdby'                         => $checkUser->idnumber,
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
                'createdby'                             => $checkUser->idnumber,
                'created_at'                            => Carbon::now(),
            ];

            $patientCourseInTheWardData = [
                'patient_Id'                            => $patient_id,
                'case_No'                               => $registry_id,
                'doctors_OrdersAction'                  => $request->payload['doctors_OrdersAction'] ?? null,
                'createdby'                             => $checkUser->idnumber,
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
                'createdby'                 => $checkUser->idnumber,
                'created_at'                => Carbon::now(),
            ];

            $patientPhysicalExamtionGeneralSurveyData = [
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'awake_And_Alert'       => $request->payload['awake_And_Alert'] ?? null,
                'altered_Sensorium'     => $request->payload['altered_Sensorium'] ?? null,
                'createdby'             => $checkUser->idnumber,
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
                'createdby'                     => $checkUser->idnumber,
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
                'createdby'                         => $checkUser->idnumber,
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
                'createdby'                     => $checkUser->idnumber,
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
                'createdby'                 => $checkUser->idnumber,
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
                'createdby'                                             => $checkUser->idnumber,
                'created_at'                                            => Carbon::now(),
            ];

            $patientPregnancyHistoryData = [
                'OBGYNHistoryID'    => $patient_id,
                'pregnancyNumber'   => $registry_id,
                'outcome'           => $request->payload['outcome'] ?? null,
                'deliveryDate'      => $request->payload['deliveryDate'] ?? null,
                'complications'     => $request->payload['complications'] ?? null,
                'createdby'         => $checkUser->idnumber,
                'created_at'        => Carbon::now(),
            ];

            $patientGynecologicalConditions = [
                'OBGYNHistoryID'    => $patient_id,
                'conditionName'     => $registry_id,
                'diagnosisDate'     => $request->payload['diagnosisDate'] ?? null,
                'createdby'         => $checkUser->idnumber,
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
                'createdby'             => $checkUser->idnumber,
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
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now()
            ];

            $privilegedPointTransfers = [
                'fromCard_Id'       => '',
                'toCard_Id'         => $request->payload['toCard_Id'] ?? 4,
                'transaction_Date'  => Carbon::now(),
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => $checkUser->idnumber,
                'created_at'        => Carbon::now()
            ];

            $privilegedPointTransactions = [
                'card_Id'           => '',
                'transaction_Date'  => Carbon::now(),
                'transaction_Type'  => $request->payload['transaction_Type'] ?? 'Test Transaction',
                'description'       => $request->payload['description'] ?? null,
                'points'            => $request->payload['points'] ?? 1000,
                'createdby'         => $checkUser->idnumber,
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
                'createdby'                         => $checkUser->idnumber,
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
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpTreatment = [
                'instruction_Id'        => '',
                'treatment_Description' => $request->payload['treatment_Description'] ?? null,
                'treatment_Date'        => $request->payload['treatment_Date'] ?? null,
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'notes'                 => $request->payload['notes'] ?? null,
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now()
            ];

            $patientDischargeFollowUpLaboratories = [
                'instruction_Id'    => '',
                'item_Id'           => $request->payload['item_Id'] ?? null,
                'test_Name'         => $request->payload['test_Name'] ?? null,
                'test_DateTime'     => $request->payload['test_DateTime'] ?? null,
                'notes'             => $request->payload['notes'] ?? null,
                'createdby'         => $checkUser->idnumber,
                'created_at'        => Carbon::now()
            ];

            $patientDischargeDoctorsFollowUp = [
                'instruction_Id'        => '',
                'doctor_Id'             => $request->payload['doctor_Id'] ?? null,
                'doctor_Name'           => $request->payload['doctor_Name'] ?? null,
                'doctor_Specialization' => $request->payload['doctor_Specialization'] ?? null,
                'schedule_Date'         => $request->payload['schedule_Date'] ?? null,
                'createdby'             => $checkUser->idnumber,
                'created_at'            => Carbon::now()
            ];


            $today = Carbon::now()->format('Y-m-d');

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();
    
            
            //Insert Data Function
            $patient = Patient::updateOrCreate(
                $patientRule,  
                $this->preparePatientData($request, $checkUser, $currentTimestamp, $patient_id)
            );
            $patient->past_medical_procedures()->create($pastientPastMedicalProcedureData);
            $patient->past_medical_history()->create($patientPastMedicalHistoryData);
            $patient->past_immunization()->create($patientPastImmunizationData);
            $patient->past_bad_habits()->create($patientPastBadHabitsData);

            $patientPriviledgeCard = $patient->privilegedCard()->create($patientPrivilegedCard);
            $privilegedPointTransfers['fromCard_Id'] = $patientPriviledgeCard->id;
            $privilegedPointTransfers['toCard_Id'] = $patientPriviledgeCard->id;
            $privilegedPointTransactions['card_Id'] = $patientPriviledgeCard->id;
            $patientPriviledgeCard->pointTransactions()->create($privilegedPointTransactions);
            $patientPriviledgeCard->pointTransfers()->create($privilegedPointTransfers);
    
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

                if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
                    foreach($request->payload['selectedAllergy'] as $allergy) {

                        $commonData = [
                            'patient_Id'            => $patient_id,
                            'case_No'               => $registry_id,
                            'createdby'             => $checkUser->idnumber,
                            'created_at'            => Carbon::now(),
                            'isDeleted'             => 0,
                        ];
        
                        $patientAllergyData         = array_merge($commonData, [

                            'allergy_type_id'       => $allergy['allergy_id'],
                            'allergy_description'   => $allergy['allergy_name'] ?? null,
                            'family_History'        => $request->payload['family_History'] ?? null,
        
                        ]);

                        $patientAllergy             = $patientRegistry->allergies()->create($patientAllergyData);
                        $last_inserted_id           = $patientAllergy->id;

                        $patientCauseAllergyData    = array_merge($commonData, [

                            'assessID'              => $last_inserted_id,
                            'allergy_Type_Id'       => $allergy['allergy_id'],
                            'description'           => $allergy['cause'],
                            'duration'              => $request->payload['duration'] ?? null,

                        ]);
                        
                        $patientAllergy->cause_of_allergy()->create($patientCauseAllergyData);
                    
                        if (isset($allergy['symptoms']) && is_array($allergy['symptoms'])) {
                            foreach ($allergy['symptoms'] as $symptom) {
                                $patientSymptomsOfAllergy   = array_merge($commonData,  [

                                    'assessID'              => $last_inserted_id,
                                    'allergy_Type_Id'       => $allergy['allergy_id'],
                                    'symptom_id'            => $symptom['id'],
                                    'symptom_Description'   => $symptom['description'] ?? null,

                                ]);
        
                                $patientAllergy->symptoms_allergy()->create($patientSymptomsOfAllergy);
                            }
                        }

                        $patientDrugUsedForAllergyData  = array_merge($commonData, [

                            'assessID'                  => $last_inserted_id,
                            'allergy_Type_Id'           => $allergy['allergy_id'],
                            'drug_Description'          => $request->payload['drug_Description'] ?? null,

                        ]);

                        $patientAllergy->drug_used_for_allergy()->create($patientDrugUsedForAllergyData);
                    }
                }

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
                echo 'Failed Here';
                throw new \Exception('Error');
            endif;

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_patient_data')->commit();
            DB::connection('sqlsrv')->commit();
            
            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 201);

        } catch(\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            DB::connection('sqlsrv')->rollBack();

            return response()->json([
                'message' => 'Failed to register patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {

        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {

            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([
                    'message' => 'Incorrect Username or Password',
                ], 404);
            endif;

            $registry_sequence      = SystemSequence::where('code', 'MERN')->select('seq_no', 'recent_generated')->first();
            $registry_mopd_sequence = SystemSequence::where('code', 'MOPD')->first();
            $er_case_sequence       = SystemSequence::where('code', 'SERCN')->select('seq_no', 'recent_generated')->first();

            $today = Carbon::now()->format('Y-m-d');
            $userId = Auth()->user()->idnumber;
            $currentTimestamp = Carbon::now();

            $patient = Patient::where('patient_Id', $id)->first();
            
            if($patient):

                $patient_id = $patient->patient_Id;
            else:

                $patient_id = $request->payload['patient_Id'];
                
                $patient =  Patient::updateOrCreate(
                    ['patient_Id' => $id], 
                        $this->preparePatientData($request, $checkUser, $currentTimestamp)
                        );

            endif;

                $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

                if($this->check_is_allow_medsys) {

                    $useSequence = $this->handleMedsysRegistry($existingRegistry, $request, $registry_sequence, $registry_mopd_sequence, $er_case_sequence);
                    $registry_id = $useSequence['registryId'];
                    $er_Case_No  = $useSequence['erCaseNo'];

                } else {

                    $useSequence = $this->handleNonMedsysRegistry($existingRegistry, $request, $registry_sequence, $er_case_sequence,  $registry_mopd_sequence);
                    $registry_id = $useSequence['registryId'];
                    $er_Case_No = $useSequence['erCaseNo'];
                }

                $mergeToPatientRelatedTable = [
                    'patient_Id' => $patient_id,
                    'createdBy'  => $checkUser->idnumber,
                    'created_at' => $currentTimestamp
                ];

                $mergeToRegistryRelatedTable = [
                    'patient_Id' => $patient_id,
                    'case_No'    => $registry_id,
                    'createdBy'  => $checkUser->idnumber,
                    'created_at' => $currentTimestamp
                ];

                
                $checkPatient = ['patient_Id'   => $patient_id];
                
                $pastImmunization               = $patient->past_immunization()->whereDate('created_at', $today)->first() ?: null;
                $pastMedicalHistory             = $patient->past_medical_history()->whereDate('created_at', $today)->first() ?: null;
                $pastMedicalProcedure           = $patient->past_medical_procedures()->whereDate('created_at', $today)->first() ?: null;
                $pastBadHabits                  = $patient->past_bad_habits()->whereDate('created_at', $today)->first() ?: null;

                $patientRegistry                = $patient->patientRegistry()->whereDate('created_at', $today)->first() ?: null;

                $patientHistory                 = $patientRegistry && $patientRegistry->history()
                                                ? $patientRegistry->history()->whereDate('created_at', $today)->first() 
                                                : null;

                $patientMedicalProcedure        = $patientRegistry && $patientRegistry->medical_procedures()
                                                ? $patientRegistry->medical_procedures()->whereDate('created_at', $today)->first() 
                                                : null;

                $patientVitalSign               = $patientRegistry && $patientRegistry->vitals()
                                                ? $patientRegistry->vitals()->whereDate('created_at', $today)->first() 
                                                : null;

                $patientImmunization            = $patientRegistry && $patientRegistry->immunizations()
                                                ? $patientRegistry->immunizations()->whereDate('created_at', $today)->first() 
                                                : null;

                $patientAdministeredMedicine    = $patientRegistry && $patientRegistry->administered_medicines()
                                                ? $patientRegistry->administered_medicines()->whereDate('created_at', $today)->first() 
                                                : null;

                $OBGYNHistory                   = $patientRegistry && $patientRegistry->oBGYNHistory()
                                                ? $patientRegistry->oBGYNHistory()->whereDate('created_at', $today)->first() 
                                                : null;

                $pregnancyHistory               = $patientRegistry && $OBGYNHistory->PatientPregnancyHistory()
                                                ? $OBGYNHistory->PatientPregnancyHistory()->whereDate('created_at', $today)->first() 
                                                : null;

                $gynecologicalConditions        = $patientRegistry && $OBGYNHistory->gynecologicalConditions()
                                                ? $OBGYNHistory->gynecologicalConditions()->whereDate('created_at', $today)->first() 
                                                : null;

                $allergy                        = $patientRegistry && $patientRegistry->allergies()
                                                ? $patientRegistry->allergies()->whereDate('created_at', $today)->first() 
                                                : null;
        
                $badHabits                      = $patientRegistry && $patientRegistry->bad_habits()
                                                ? $patientRegistry->bad_habits()->whereDate('created_at', $today)->first() 
                                                : null;

                $patientDoctors                 = $patientRegistry && $patientRegistry->patientDoctors()
                                                ? $patientRegistry->patientDoctors()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalAbdomen                = $patientRegistry && $patientRegistry->physicalAbdomen()
                                                ? $patientRegistry->physicalAbdomen()->whereDate('created_at', $today)->first() 
                                                : null;

                $pertinentSignAndSymptoms       = $patientRegistry && $patientRegistry->pertinentSignAndSymptoms()
                                                ? $patientRegistry->pertinentSignAndSymptoms()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalExamtionChestLungs     = $patientRegistry && $patientRegistry->physicalExamtionChestLungs()
                                                ? $patientRegistry->physicalExamtionChestLungs()->whereDate('created_at', $today)->first() 
                                                : null;

                $courseInTheWard                = $patientRegistry && $patientRegistry->courseInTheWard()
                                                ? $patientRegistry->courseInTheWard()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalExamtionCVS            = $patientRegistry && $patientRegistry->physicalExamtionCVS()
                                                ? $patientRegistry->physicalExamtionCVS()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalExamtionGeneralSurvey  = $patientRegistry && $patientRegistry->physicalExamtionGeneralSurvey()
                                                ? $patientRegistry->physicalExamtionGeneralSurvey()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalExamtionHEENT          = $patientRegistry && $patientRegistry->physicalExamtionHEENT()
                                                ? $patientRegistry->physicalExamtionHEENT()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalGUIE                   = $patientRegistry && $patientRegistry->physicalGUIE()
                                                ? $patientRegistry->physicalGUIE()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalNeuroExam              = $patientRegistry && $patientRegistry->physicalNeuroExam()
                                                ? $patientRegistry->physicalNeuroExam()->whereDate('created_at', $today)->first() 
                                                : null;

                $physicalSkinExtremities        = $patientRegistry && $patientRegistry->physicalSkinExtremities()
                                                ? $patientRegistry->physicalSkinExtremities()->whereDate('created_at', $today)->first() 
                                                : null;

                $medications                    = $patientRegistry && $patientRegistry->medications()
                                                ? $patientRegistry->medications()->whereDate('created_at', $today)->first() 
                                                : null;

                $dischargeInstructions          = $patientRegistry && $patientRegistry->dischargeInstructions()
                                                ? $patientRegistry->dischargeInstructions()->whereDate('created_at', $today)->first() 
                                                : null;

                $dischargeMedications           = $patientRegistry && $dischargeInstructions->dischargeMedications()
                                                ? $dischargeInstructions->dischargeMedications()->whereDate('created_at', $today)->first() 
                                                : null;

                $dischargeFollowUpTreatment     = $patientRegistry && $dischargeInstructions->dischargeFollowUpTreatment()
                                                ? $dischargeInstructions->dischargeFollowUpTreatment()->whereDate('created_at', $today)->first() 
                                                : null;

                $dischargeFollowUpLaboratories  = $patientRegistry && $dischargeInstructions->dischargeFollowUpLaboratories()
                                                ? $dischargeInstructions->dischargeFollowUpLaboratories()->whereDate('created_at', $today)->first() 
                                                : null;

                $dischargeDoctorsFollowUp       = $patientRegistry && $dischargeInstructions->dischargeDoctorsFollowUp()
                                                ? $dischargeInstructions->dischargeDoctorsFollowUp()->whereDate('created_at', $today)->first() 
                                                : null;

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
                    'title_id'                  => Arr::get($request->payload, 'title_id', optional($patient)->title_id),
                    'lastname'                  => ucwords(Arr::get($request->payload, 'lastname', optional($patient)->lastname)),
                    'firstname'                 => ucwords(Arr::get($request->payload, 'firstname', optional($patient)->firstname)),
                    'middlename'                => ucwords(Arr::get($request->payload, 'middlename', optional($patient)->middlename)),
                    'suffix_id'                 => Arr::get($request->payload, 'suffix_id', optional($patient)->suffix_id),
                    'birthdate'                 => Arr::get($request->payload, 'birthdate', optional($patient)->birthdate),
                    'birthtime'                 => Arr::get($request->payload, 'birthtime', optional($patient)->birthtime),
                    'birthplace'                => Arr::get($request->payload, 'birthplace', optional($patient)->birthplace),
                    'age'                       => Arr::get($request->payload, 'age', optional($patient)->age),
                    'sex_id'                    => Arr::get($request->payload, 'sex_id', optional($patient)->sex_id),
                    'nationality_id'            => Arr::get($request->payload, 'nationality_id', optional($patient)->nationality_id),
                    'citizenship_id'            => Arr::get($request->payload, 'citizenship_id', optional($patient)->citizenship_id),
                    'complexion'                => Arr::get($request->payload, 'complexion', optional($patient)->complexion),
                    'haircolor'                 => Arr::get($request->payload, 'haircolor', optional($patient)->haircolor),
                    'eyecolor'                  => Arr::get($request->payload, 'eyecolor', optional($patient)->eyecolor),
                    'height'                    => Arr::get($request->payload, 'height', optional($patient)->height),
                    'weight'                    => Arr::get($request->payload, 'weight', optional($patient)->weight),
                    'religion_id'               => Arr::get($request->payload, 'religion_id', optional($patient)->religion_id),
                    'civilstatus_id'            => Arr::get($request->payload, 'civilstatus_id', optional($patient)->civilstatus_id),
                    'bloodtype_id'              => Arr::get($request->payload, 'bloodtype_id', optional($patient)->bloodtype_id),
                    'dialect_spoken'            => Arr::get($request->payload, 'dialect_spoken', optional($patient)->dialect_spoken),
                    'bldgstreet'                => Arr::get($request->payload, 'address.bldgstreet', optional($patient)->bldgstreet),
                    'region_id'                 => Arr::get($request->payload, 'address.region_id', optional($patient)->region_id),
                    'province_id'               => Arr::get($request->payload, 'address.province_id', optional($patient)->province_id),
                    'municipality_id'           => Arr::get($request->payload, 'address.municipality_id', optional($patient)->municipality_id),
                    'barangay_id'               => Arr::get($request->payload, 'address.barangay_id', optional($patient)->barangay_id),
                    'country_id'                => Arr::get($request->payload, 'address.country_id', optional($patient)->country_id),
                    'zipcode_id'                => Arr::get($request->payload, 'zipcode_id', optional($patient)->zipcode_id),
                    'occupation'                => Arr::get($request->payload, 'occupation', optional($patient)->occupation),
                    'dependents'                => Arr::get($request->payload, 'dependents', optional($patient)->dependents),
                    'telephone_number'          => Arr::get($request->payload, 'telephone_number', optional($patient)->telephone_number),
                    'mobile_number'             => Arr::get($request->payload, 'mobile_number', optional($patient)->mobile_number),
                    'email_address'             => Arr::get($request->payload, 'email_address', optional($patient)->email_address),
                    'isSeniorCitizen'           => Arr::get($request->payload, 'isSeniorCitizen', false),
                    'SeniorCitizen_ID_Number'   => Arr::get($request->payload, 'SeniorCitizen_ID_Number', optional($patient)->SeniorCitizen_ID_Number),
                    'isPWD'                     => Arr::get($request->payload, 'isPWD', false),
                    'PWD_ID_Number'             => Arr::get($request->payload, 'PWD_ID_Number', optional($patient)->PWD_ID_Number),
                    'isPhilhealth_Member'       => Arr::get($request->payload, 'isPhilhealth_Member', false),
                    'Philhealth_Number'         => Arr::get($request->payload, 'Philhealth_Number', optional($patient)->Philhealth_Number),
                    'isEmployee'                => Arr::get($request->payload, 'isEmployee', false),
                    'GSIS_Number'               => Arr::get($request->payload, 'GSIS_Number', optional($patient)->GSIS_Number),
                    'SSS_Number'                => Arr::get($request->payload, 'SSS_Number', optional($patient)->SSS_Number),
                    'passport_number'           => Arr::get($request->payload, 'passport_number', optional($patient)->passport_number),
                    'seaman_book_number'        => Arr::get($request->payload, 'seaman_book_number', optional($patient)->seaman_book_number),
                    'embarked_date'             => Arr::get($request->payload, 'embarked_date', optional($patient)->embarked_date),
                    'disembarked_date'          => Arr::get($request->payload, 'disembarked_date', optional($patient)->disembarked_date),
                    'xray_number'               => Arr::get($request->payload, 'xray_number', optional($patient)->xray_number),
                    'ultrasound_number'         => Arr::get($request->payload, 'ultrasound_number', optional($patient)->ultrasound_number),
                    'ct_number'                 => Arr::get($request->payload, 'ct_number', optional($patient)->ct_number),
                    'petct_number'              => Arr::get($request->payload, 'petct_number', optional($patient)->petct_number),
                    'mri_number'                => Arr::get($request->payload, 'mri_number', optional($patient)->mri_number),
                    'mammo_number'              => Arr::get($request->payload, 'mammo_number', optional($patient)->mammo_number),
                    'OB_number'                 => Arr::get($request->payload, 'OB_number', optional($patient)->OB_number),
                    'nuclearmed_number'         => Arr::get($request->payload, 'nuclearmed_number', optional($patient)->nuclearmed_number),
                    'typeofdeath_id'            => Arr::get($request->payload, 'typeofdeath_id', optional($patient)->typeofdeath_id),
                    'dateofdeath'               => Arr::get($request->payload, 'dateofdeath', optional($patient)->dateofdeath),
                    'timeofdeath'               => Arr::get($request->payload, 'timeofdeath', optional($patient)->timeofdeath),
                    'spDateMarried'             => Arr::get($request->payload, 'spDateMarried', optional($patient)->spDateMarried),
                    'spLastname'                => Arr::get($request->payload, 'spLastname', optional($patient)->spLastname),
                    'spFirstname'               => Arr::get($request->payload, 'spFirstname', optional($patient)->spFirstname),
                    'spMiddlename'              => Arr::get($request->payload, 'spMiddlename', optional($patient)->spMiddlename),
                    'spSuffix_id'               => Arr::get($request->payload, 'spSuffix_id', optional($patient)->spSuffix_id),
                    'spAddress'                 => Arr::get($request->payload, 'spAddress', optional($patient)->spAddress),
                    'sptelephone_number'        => Arr::get($request->payload, 'sptelephone_number', optional($patient)->sptelephone_number),
                    'spmobile_number'           => Arr::get($request->payload, 'spmobile_number', optional($patient)->spmobile_number),
                    'spOccupation'              => Arr::get($request->payload, 'spOccupation', optional($patient)->spOccupation),
                    'spBirthdate'               => Arr::get($request->payload, 'spBirthdate', optional($patient)->spBirthdate),
                    'spAge'                     => Arr::get($request->payload, 'spAge', optional($patient)->spAge),
                    'motherLastname'            => Arr::get($request->payload, 'motherLastname', optional($patient)->motherLastname),
                    'motherFirstname'           => Arr::get($request->payload, 'motherFirstname', optional($patient)->motherFirstname),
                    'motherMiddlename'          => Arr::get($request->payload, 'motherMiddlename', optional($patient)->motherMiddlename),
                    'motherSuffix_id'           => Arr::get($request->payload, 'motherSuffix_id', optional($patient)->motherSuffix_id),
                    'motherAddress'             => Arr::get($request->payload, 'motherAddress', optional($patient)->motherAddress),
                    'mothertelephone_number'    => Arr::get($request->payload, 'mothertelephone_number', optional($patient)->mothertelephone_number),
                    'mothermobile_number'       => Arr::get($request->payload, 'mothermobile_number', optional($patient)->mothermobile_number),
                    'motherOccupation'          => Arr::get($request->payload, 'motherOccupation', optional($patient)->motherOccupation), 
                    'motherBirthdate'           => Arr::get($request->payload, 'motherBirthdate', optional($patient)->motherBirthdate),
                    'motherAge'                 => Arr::get($request->payload, 'motherAge', optional($patient)->motherAge),
                    'fatherLastname'            => Arr::get($request->payload, 'fatherLastname', optional($patient)->fatherLastname),
                    'fatherFirstname'           => Arr::get($request->payload, 'fatherFirstname', optional($patient)->fatherFirstname),
                    'fatherMiddlename'          => Arr::get($request->payload, 'fatherMiddlename', optional($patient)->fatherMiddlename),
                    'fatherSuffix_id'           => Arr::get($request->payload, 'fatherSuffix_id', optional($patient)->fatherSuffix_id),
                    'fatherAddress'             => Arr::get($request->payload, 'fatherAddress', optional($patient)->fatherAddress),
                    'fathertelephone_number'    => Arr::get($request->payload, 'fathertelephone_number', optional($patient)->fathertelephone_number),
                    'fathermobile_number'       => Arr::get($request->payload, 'fathermobile_number', optional($patient)->fathermobile_number),
                    'fatherOccupation'          => Arr::get($request->payload, 'fatherOccupation', optional($patient)->fatherOccupation),
                    'fatherBirthdate'           => Arr::get($request->payload, 'fatherBirthdate', optional($patient)->fatherBirthdate),
                    'fatherAge'                 => Arr::get($request->payload, 'fatherAge', optional($patient)->fatherAge),
                    'portal_access_uid'         => Arr::get($request->payload, 'portal_access_uid', optional($patient)->portal_access_uid),
                    'portal_access_pwd'         => Arr::get($request->payload, 'portal_access_pwd', optional($patient)->portal_access_pwd),
                    'isBlacklisted'             => Arr::get($request->payload, 'isBlacklisted', optional($patient)->isBlacklisted),
                    'blacklist_reason'          => Arr::get($request->payload, 'blacklist_reason', optional($patient)->blacklist_reason),
                    'isAbscond'                 => Arr::get($request->payload, 'isAbscond', false),
                    'abscond_details'           => Arr::get($request->payload, 'abscond_details', optional($patient)->abscond_details),
                    'is_old_patient'            => Arr::get($request->payload, 'is_old_patient', optional($patient)->is_old_patient),
                    'patient_picture'           => Arr::get($request->payload, 'patient_picture', optional($patient)->patient_picture),
                    'patient_picture_path'      => Arr::get($request->payload, 'patient_picture_path', optional($patient)->patient_picture_path),
                    'branch_id'                 => Arr::get($request->payload, 'branch_id', optional($patient)->branch_id),
                    'previous_patient_id'       => Arr::get($request->payload, 'previous_patient_id', optional($patient)->previous_patient_id),
                    'medsys_patient_id'         => Arr::get($request->payload, 'medsys_patient_id', optional($patient)->medsys_patient_id),
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,   
                ];

                $patientPastImmunizationData = [
                    'branch_Id'             => 1,
                    'vaccine_Id'            => 1,
                    'administration_Date'   => Arr::get($request->payload, 'administration_Date', optional($pastImmunization)->administration_Date),
                    'dose'                  => Arr::get($request->payload, 'dose', optional($pastImmunization)->dose),
                    'site'                  => Arr::get($request->payload, 'site', optional($pastImmunization)->site),
                    'administrator_Name'    => Arr::get($request->payload, 'administrator_Name', optional($pastImmunization)->administrator_Name),
                    'notes'                 => Arr::get($request->payload, 'notes', optional($pastImmunization)->notes),
                    'updatedby'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientPastMedicalHistoryData = [
                    'diagnose_Description'      => Arr::get($request->payload, 'diagnose_Description', optional($pastMedicalHistory)->diagnose_Description),
                    'diagnosis_Date'            => Arr::get($request->payload, 'diagnosis_Date', optional($pastMedicalHistory)->diagnosis_Date),
                    'treament'                  => Arr::get($request->payload, 'treament', optional($pastMedicalHistory)->treament),
                    'updatedby'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];

                $pastientPastMedicalProcedureData = [
                    'description'               => Arr::get($request->payload, 'description', optional($pastMedicalProcedure)->description),
                    'date_Of_Procedure'         => Arr::get($request->payload, 'date_Of_Procedure', optional($pastMedicalProcedure)->date_Of_Procedure),
                    'updatedby'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];

                $patientBadHabitsData = [
                    'description'   => Arr::get($request->payload, 'description', optional($badHabits)->description),
                    'updatedBy'     => $checkUser->idnumber,
                    'updated_at'    => $currentTimestamp,
                ];

                $patientPastBadHabitsData = [
                    'description'   => Arr::get($request->payload, 'description', optional($pastBadHabits)->description),
                    'updatedBy'     => $checkUser->idnumber,
                    'updated_at'    => $currentTimestamp,
                ];

                $patientDoctorsData = [
                    'doctor_Id'         => Arr::get($request->payload, 'doctor_Id', optional($patientDoctors)->doctor_Id),
                    'doctors_Fullname'  => Arr::get($request->payload, 'doctors_Fullname', optional($patientDoctors)->doctors_Fullname),
                    'role_Id'           => Arr::get($request->payload, 'role_Id', optional($patientDoctors)->role_Id),
                    'updatedBy'         => $checkUser->idnumber,
                    'updated_at'        => $currentTimestamp,
                ];

                $patientPhysicalAbdomenData = [
                    'essentially_Normal'        => Arr::get($request->payload, 'essentially_Normal', optional($physicalAbdomen)->essentially_Normal),
                    'palpable_Masses'           => Arr::get($request->payload, 'palpable_Masses', optional($physicalAbdomen)->palpable_Masses),
                    'abdominal_Rigidity'        => Arr::get($request->payload, 'abdominal_Rigidity', optional($physicalAbdomen)->abdominal_Rigidity),
                    'uterine_Contraction'       => Arr::get($request->payload, 'uterine_Contraction', optional($physicalAbdomen)->uterine_Contraction),
                    'hyperactive_Bowel_Sounds'  => Arr::get($request->payload, 'hyperactive_Bowel_Sounds', optional($physicalAbdomen)->hyperactive_Bowel_Sounds),
                    'others_Description'        => Arr::get($request->payload, 'others_Description', optional($physicalAbdomen)->others_Description),
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];

                $patientPertinentSignAndSymptomsData = [
                    'altered_Mental_Sensorium'        => Arr::get($request->payload, 'altered_Mental_Sensorium', optional($pertinentSignAndSymptoms)->altered_Mental_Sensorium),
                    'abdominal_CrampPain'             => Arr::get($request->payload, 'abdominal_CrampPain', optional($pertinentSignAndSymptoms)->abdominal_CrampPain),
                    'anorexia'                        => Arr::get($request->payload, 'anorexia', optional($pertinentSignAndSymptoms)->anorexia),
                    'bleeding_Gums'                   => Arr::get($request->payload, 'bleeding_Gums', optional($pertinentSignAndSymptoms)->bleeding_Gums),
                    'body_Weakness'                   => Arr::get($request->payload, 'body_Weakness', optional($pertinentSignAndSymptoms)->body_Weakness),
                    'blurring_Of_Vision'              => Arr::get($request->payload, 'blurring_Of_Vision', optional($pertinentSignAndSymptoms)->blurring_Of_Vision),
                    'chest_PainDiscomfort'            => Arr::get($request->payload, 'chest_PainDiscomfort', optional($pertinentSignAndSymptoms)->chest_PainDiscomfort),
                    'constipation'                    => Arr::get($request->payload, 'constipation', optional($pertinentSignAndSymptoms)->constipation),
                    'cough'                           => Arr::get($request->payload, 'cough', optional($pertinentSignAndSymptoms)->cough),
                    'diarrhea'                        => Arr::get($request->payload, 'diarrhea', optional($pertinentSignAndSymptoms)->diarrhea),
                    'dizziness'                       => Arr::get($request->payload, 'dizziness', optional($pertinentSignAndSymptoms)->dizziness),
                    'dysphagia'                       => Arr::get($request->payload, 'dysphagia', optional($pertinentSignAndSymptoms)->dysphagia),
                    'dysuria'                         => Arr::get($request->payload, 'dysuria', optional($pertinentSignAndSymptoms)->dysuria),
                    'epistaxis'                       => Arr::get($request->payload, 'epistaxis', optional($pertinentSignAndSymptoms)->epistaxis),
                    'fever'                           => Arr::get($request->payload, 'fever', optional($pertinentSignAndSymptoms)->fever),
                    'frequency_Of_Urination'          => Arr::get($request->payload, 'frequency_Of_Urination', optional($pertinentSignAndSymptoms)->frequency_Of_Urination),
                    'headache'                        => Arr::get($request->payload, 'headache', optional($pertinentSignAndSymptoms)->headache),
                    'hematemesis'                     => Arr::get($request->payload, 'hematemesis', optional($pertinentSignAndSymptoms)->hematemesis),
                    'hematuria'                       => Arr::get($request->payload, 'hematuria', optional($pertinentSignAndSymptoms)->hematuria),
                    'hemoptysis'                      => Arr::get($request->payload, 'hemoptysis', optional($pertinentSignAndSymptoms)->hemoptysis),
                    'irritability'                    => Arr::get($request->payload, 'irritability', optional($pertinentSignAndSymptoms)->irritability),
                    'jaundice'                        => Arr::get($request->payload, 'jaundice', optional($pertinentSignAndSymptoms)->jaundice),
                    'lower_Extremity_Edema'           => Arr::get($request->payload, 'lower_Extremity_Edema', optional($pertinentSignAndSymptoms)->lower_Extremity_Edema),
                    'myalgia'                         => Arr::get($request->payload, 'myalgia', optional($pertinentSignAndSymptoms)->myalgia),
                    'orthopnea'                       => Arr::get($request->payload, 'orthopnea', optional($pertinentSignAndSymptoms)->orthopnea),
                    'pain'                            => Arr::get($request->payload, 'pain', optional($pertinentSignAndSymptoms)->pain),
                    'pain_Description'                => Arr::get($request->payload, 'pain_Description', optional($pertinentSignAndSymptoms)->pain_Description),
                    'palpitations'                    => Arr::get($request->payload, 'palpitations', optional($pertinentSignAndSymptoms)->palpitations),
                    'seizures'                        => Arr::get($request->payload, 'seizures', optional($pertinentSignAndSymptoms)->seizures),
                    'skin_rashes'                     => Arr::get($request->payload, 'skin_rashes', optional($pertinentSignAndSymptoms)->skin_rashes),
                    'stool_BloodyBlackTarry_Mucoid'   => Arr::get($request->payload, 'stool_BloodyBlackTarry_Mucoid', optional($pertinentSignAndSymptoms)->stool_BloodyBlackTarry_Mucoid),
                    'sweating'                        => Arr::get($request->payload, 'sweating', optional($pertinentSignAndSymptoms)->sweating),
                    'urgency'                         => Arr::get($request->payload, 'urgency', optional($pertinentSignAndSymptoms)->urgency),
                    'vomitting'                       => Arr::get($request->payload, 'vomitting', optional($pertinentSignAndSymptoms)->vomitting),
                    'weightloss'                      => Arr::get($request->payload, 'weightloss', optional($pertinentSignAndSymptoms)->weightloss),
                    'others'                          => Arr::get($request->payload, 'others', optional($pertinentSignAndSymptoms)->others),
                    'others_Description'              => Arr::get($request->payload, 'others_Description', optional($pertinentSignAndSymptoms)->others_Description),
                    'updatedBy'                       => $checkUser->idnumber,
                    'updated_at'                      => $currentTimestamp,
                ];

                $patientPhysicalExamtionChestLungsData = [
                    'essentially_Normal'                    => Arr::get($request->payload, 'essentially_Normal', optional($physicalExamtionChestLungs)->essentially_Normal),
                    'lumps_Over_Breasts'                    => Arr::get($request->payload, 'lumps_Over_Breasts', optional($physicalExamtionChestLungs)->lumps_Over_Breasts),
                    'asymmetrical_Chest_Expansion'          => Arr::get($request->payload, 'asymmetrical_Chest_Expansion', optional($physicalExamtionChestLungs)->asymmetrical_Chest_Expansion),
                    'rales_Crackles_Rhonchi'                => Arr::get($request->payload, 'rales_Crackles_Rhonchi', optional($physicalExamtionChestLungs)->rales_Crackles_Rhonchi),
                    'decreased_Breath_Sounds'               => Arr::get($request->payload, 'decreased_Breath_Sounds', optional($physicalExamtionChestLungs)->decreased_Breath_Sounds),
                    'intercostalrib_Clavicular_Retraction'  => Arr::get($request->payload, 'intercostalrib_Clavicular_Retraction', optional($physicalExamtionChestLungs)->intercostalrib_Clavicular_Retraction),
                    'wheezes'                               => Arr::get($request->payload, 'wheezes', optional($physicalExamtionChestLungs)->wheezes),
                    'others_Description'                    => Arr::get($request->payload, 'others_Description', optional($physicalExamtionChestLungs)->others_Description),
                    'updatedBy'                             => $checkUser->idnumber,
                    'updated_at'                            => $currentTimestamp,
                ];

                $patientCourseInTheWardData = [
                    'doctors_OrdersAction'  => Arr::get($request->payload, 'doctors_OrdersAction', optional($courseInTheWard)->doctors_OrdersAction),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientPhysicalExamtionCVSData = [
                    'essentially_Normal'        => Arr::get($request->payload, 'essentially_Normal', optional($physicalExamtionCVS)->essentially_Normal),
                    'irregular_Rhythm'          => Arr::get($request->payload, 'irregular_Rhythm', optional($physicalExamtionCVS)->irregular_Rhythm),
                    'displaced_Apex_Beat'       => Arr::get($request->payload, 'displaced_Apex_Beat', optional($physicalExamtionCVS)->displaced_Apex_Beat),
                    'muffled_Heart_Sounds'      => Arr::get($request->payload, 'muffled_Heart_Sounds', optional($physicalExamtionCVS)->muffled_Heart_Sounds),
                    'heaves_AndOR_Thrills'      => Arr::get($request->payload, 'heaves_AndOR_Thrills', optional($physicalExamtionCVS)->heaves_AndOR_Thrills),
                    'murmurs'                   => Arr::get($request->payload, 'murmurs', optional($physicalExamtionCVS)->murmurs),
                    'pericardial_Bulge'         => Arr::get($request->payload, 'pericardial_Bulge', optional($physicalExamtionCVS)->pericardial_Bulge),
                    'others_Description'        => Arr::get($request->payload, 'others_Description', optional($physicalExamtionCVS)->others_Description),
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];

                $patientPhysicalExamtionGeneralSurveyData = [
                    'awake_And_Alert'       => Arr::get($request->payload, 'awake_And_Alert', optional($physicalExamtionGeneralSurvey)->awake_And_Alert),
                    'altered_Sensorium'     => Arr::get($request->payload, 'altered_Sensorium', optional($physicalExamtionGeneralSurvey)->altered_Sensorium),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientPhysicalExamtionHEENTData = [
                    'essentially_Normal'            => Arr::get($request->payload, 'essentially_Normal', optional($physicalExamtionHEENT)->essentially_Normal),
                    'icteric_Sclerae'               => Arr::get($request->payload, 'icteric_Sclerae', optional($physicalExamtionHEENT)->icteric_Sclerae),
                    'abnormal_Pupillary_Reaction'   => Arr::get($request->payload, 'abnormal_Pupillary_Reaction', optional($physicalExamtionHEENT)->abnormal_Pupillary_Reaction),
                    'pale_Conjunctive'              => Arr::get($request->payload, 'pale_Conjunctive', optional($physicalExamtionHEENT)->pale_Conjunctive),
                    'cervical_Lympadenopathy'       => Arr::get($request->payload, 'cervical_Lympadenopathy', optional($physicalExamtionHEENT)->cervical_Lympadenopathy),
                    'sunken_Eyeballs'               => Arr::get($request->payload, 'sunken_Eyeballs', optional($physicalExamtionHEENT)->sunken_Eyeballs),
                    'dry_Mucous_Membrane'           => Arr::get($request->payload, 'dry_Mucous_Membrane', optional($physicalExamtionHEENT)->dry_Mucous_Membrane),
                    'sunken_Fontanelle'             => Arr::get($request->payload, 'sunken_Fontanelle', optional($physicalExamtionHEENT)->sunken_Fontanelle),
                    'others_description'            => Arr::get($request->payload, 'others_description', optional($physicalExamtionHEENT)->others_description),
                    'updatedBy'                     => $checkUser->idnumber,
                    'updated_at'                    => $currentTimestamp,
                ];

                $patientPhysicalGUIEData = [
                    'essentially_Normal'                => Arr::get($request->payload, 'essentially_Normal', optional($physicalGUIE)->essentially_Normal),
                    'blood_StainedIn_Exam_Finger'       => Arr::get($request->payload, 'blood_StainedIn_Exam_Finger', optional($physicalGUIE)->blood_StainedIn_Exam_Finger),
                    'cervical_Dilatation'               => Arr::get($request->payload, 'cervical_Dilatation', optional($physicalGUIE)->cervical_Dilatation),
                    'presence_Of_AbnormalDischarge'     => Arr::get($request->payload, 'presence_Of_AbnormalDischarge', optional($physicalGUIE)->presence_Of_AbnormalDischarge),
                    'others_Description'                => Arr::get($request->payload, 'others_Description', optional($physicalGUIE)->others_Description),
                    'updatedBy'                         => $checkUser->idnumber,
                    'updated_at'                        => $currentTimestamp,
                ];

                $patientPhysicalNeuroExamData = [
                    'essentially_Normal'            => Arr::get($request->payload, 'essentially_Normal', optional($physicalNeuroExam)->essentially_Normal),
                    'abnormal_Reflexes'             => Arr::get($request->payload, 'abnormal_Reflexes', optional($physicalNeuroExam)->abnormal_Reflexes),
                    'abormal_Gait'                  => Arr::get($request->payload, 'abormal_Gait', optional($physicalNeuroExam)->abormal_Gait),
                    'poor_Altered_Memory'           => Arr::get($request->payload, 'poor_Altered_Memory', optional($physicalNeuroExam)->poor_Altered_Memory),
                    'abnormal_Position_Sense'       => Arr::get($request->payload, 'abnormal_Position_Sense', optional($physicalNeuroExam)->abnormal_Position_Sense),
                    'poor_Muscle_Tone_Strength'     => Arr::get($request->payload, 'poor_Muscle_Tone_Strength', optional($physicalNeuroExam)->poor_Muscle_Tone_Strength),
                    'abnormal_Decreased_Sensation'  => Arr::get($request->payload, 'abnormal_Decreased_Sensation', optional($physicalNeuroExam)->abnormal_Decreased_Sensation),
                    'poor_Coordination'             => Arr::get($request->payload, 'poor_Coordination', optional($physicalNeuroExam)->poor_Coordination),
                    'updatedBy'                     => $checkUser->idnumber,
                    'updated_at'                    => $currentTimestamp,
                ];

                $patientPhysicalSkinExtremitiesData = [
                    'essentially_Normal'        => Arr::get($request->payload, 'essentially_Normal', optional($physicalSkinExtremities)->essentially_Normal),
                    'edema_Swelling'            => Arr::get($request->payload, 'edema_Swelling', optional($physicalSkinExtremities)->edema_Swelling),
                    'rashes_Petechiae'          => Arr::get($request->payload, 'rashes_Petechiae', optional($physicalSkinExtremities)->rashes_Petechiae),
                    'clubbing'                  => Arr::get($request->payload, 'clubbing', optional($physicalSkinExtremities)->clubbing),
                    'decreased_Mobility'        => Arr::get($request->payload, 'decreased_Mobility', optional($physicalSkinExtremities)->decreased_Mobility),
                    'weak_Pulses'               => Arr::get($request->payload, 'weak_Pulses', optional($physicalSkinExtremities)->weak_Pulses),
                    'cold_Clammy_Skin'          => Arr::get($request->payload, 'cold_Clammy_Skin', optional($physicalSkinExtremities)->cold_Clammy_Skin),
                    'pale_Nailbeds'             => Arr::get($request->payload, 'pale_Nailbeds', optional($physicalSkinExtremities)->pale_Nailbeds),
                    'cyanosis_Mottled_Skin'     => Arr::get($request->payload, 'cyanosis_Mottled_Skin', optional($physicalSkinExtremities)->cyanosis_Mottled_Skin),
                    'poor_Skin_Turgor'          => Arr::get($request->payload, 'poor_Skin_Turgor', optional($physicalSkinExtremities)->poor_Skin_Turgor),
                    'others_Description'        => Arr::get($request->payload, 'others_Description', optional($physicalSkinExtremities)->others_Description),
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];

                $patientOBGYNHistory = [
                    'obsteric_Code'                                     => Arr::get($request->payload, 'obsteric_Code', optional($OBGYNHistory)->obsteric_Code),
                    'menarchAge'                                        => Arr::get($request->payload, 'menarchAge', optional($OBGYNHistory)->menarchAge),
                    'menopauseAge'                                      => Arr::get($request->payload, 'menopauseAge', optional($OBGYNHistory)->menopauseAge),
                    'cycleLength'                                       => Arr::get($request->payload, 'cycleLength', optional($OBGYNHistory)->cycleLength),
                    'cycleRegularity'                                   => Arr::get($request->payload, 'cycleRegularity', optional($OBGYNHistory)->cycleRegularity),
                    'lastMenstrualPeriod'                               => Arr::get($request->payload, 'lastMenstrualPeriod', optional($OBGYNHistory)->lastMenstrualPeriod),
                    'contraceptiveUse'                                  => Arr::get($request->payload, 'contraceptiveUse', optional($OBGYNHistory)->contraceptiveUse),
                    'lastPapSmearDate'                                  => Arr::get($request->payload, 'lastPapSmearDate', optional($OBGYNHistory)->lastPapSmearDate),
                    'isVitalSigns_Normal'                               => Arr::get($request->payload, 'isVitalSigns_Normal', optional($OBGYNHistory)->isVitalSigns_Normal),
                    'isAscertainPresent_PregnancyisLowRisk'             => Arr::get($request->payload, 'isAscertainPresent_PregnancyisLowRisk', optional($OBGYNHistory)->isAscertainPresent_PregnancyisLowRisk),
                    'riskfactor_MultiplePregnancy'                      => Arr::get($request->payload, 'riskfactor_MultiplePregnancy', optional($OBGYNHistory)->riskfactor_MultiplePregnancy),
                    'riskfactor_OvarianCyst'                            => Arr::get($request->payload, 'riskfactor_OvarianCyst', optional($OBGYNHistory)->riskfactor_OvarianCyst),
                    'riskfactor_MyomaUteri'                             => Arr::get($request->payload, 'riskfactor_MyomaUteri', optional($OBGYNHistory)->riskfactor_MyomaUteri),
                    'riskfactor_PlacentaPrevia'                         => Arr::get($request->payload, 'riskfactor_PlacentaPrevia', optional($OBGYNHistory)->riskfactor_PlacentaPrevia),
                    'riskfactor_Historyof3Miscarriages'                 => Arr::get($request->payload, 'riskfactor_Historyof3Miscarriages', optional($OBGYNHistory)->riskfactor_Historyof3Miscarriages),
                    'riskfactor_HistoryofStillbirth'                    => Arr::get($request->payload, 'riskfactor_HistoryofStillbirth', optional($OBGYNHistory)->riskfactor_HistoryofStillbirth),
                    'riskfactor_HistoryofEclampsia'                     => Arr::get($request->payload, 'riskfactor_HistoryofEclampsia', optional($OBGYNHistory)->riskfactor_HistoryofEclampsia),
                    'riskfactor_PrematureContraction'                   => Arr::get($request->payload, 'riskfactor_PrematureContraction', optional($OBGYNHistory)->riskfactor_PrematureContraction),
                    'riskfactor_NotApplicableNone'                      => Arr::get($request->payload, 'riskfactor_NotApplicableNone', optional($OBGYNHistory)->riskfactor_NotApplicableNone),
                    'medicalSurgical_Hypertension'                      => Arr::get($request->payload, 'medicalSurgical_Hypertension', optional($OBGYNHistory)->medicalSurgical_Hypertension),
                    'medicalSurgical_HeartDisease'                      => Arr::get($request->payload, 'medicalSurgical_HeartDisease', optional($OBGYNHistory)->medicalSurgical_HeartDisease),
                    'medicalSurgical_Diabetes'                          => Arr::get($request->payload, 'medicalSurgical_Diabetes', optional($OBGYNHistory)->medicalSurgical_Diabetes),
                    'medicalSurgical_ThyroidDisorder'                   => Arr::get($request->payload, 'medicalSurgical_ThyroidDisorder', optional($OBGYNHistory)->medicalSurgical_ThyroidDisorder),
                    'medicalSurgical_Obesity'                           => Arr::get($request->payload, 'medicalSurgical_Obesity', optional($OBGYNHistory)->medicalSurgical_Obesity),
                    'medicalSurgical_ModerateToSevereAsthma'            => Arr::get($request->payload, 'medicalSurgical_ModerateToSevereAsthma', optional($OBGYNHistory)->medicalSurgical_ModerateToSevereAsthma),
                    'medicalSurigcal_Epilepsy'                          => Arr::get($request->payload, 'medicalSurigcal_Epilepsy', optional($OBGYNHistory)->medicalSurigcal_Epilepsy),
                    'medicalSurgical_RenalDisease'                      => Arr::get($request->payload, 'medicalSurgical_RenalDisease', optional($OBGYNHistory)->medicalSurgical_RenalDisease),
                    'medicalSurgical_BleedingDisorder'                  => Arr::get($request->payload, 'medicalSurgical_BleedingDisorder', optional($OBGYNHistory)->medicalSurgical_BleedingDisorder),
                    'medicalSurgical_HistoryOfPreviousCesarianSection'  => Arr::get($request->payload, 'medicalSurgical_HistoryOfPreviousCesarianSection', optional($OBGYNHistory)->medicalSurgical_HistoryOfPreviousCesarianSection),
                    'medicalSurgical_HistoryOfUterineMyomectomy'        => Arr::get($request->payload, 'medicalSurgical_HistoryOfUterineMyomectomy', optional($OBGYNHistory)->medicalSurgical_HistoryOfUterineMyomectomy),
                    'medicalSurgical_NotApplicableNone'                 => Arr::get($request->payload, 'medicalSurgical_NotApplicableNone', optional($OBGYNHistory)->medicalSurgical_NotApplicableNone),
                    'deliveryPlan_OrientationToMCP'                     => Arr::get($request->payload, 'deliveryPlan_OrientationToMCP', optional($OBGYNHistory)->deliveryPlan_OrientationToMCP),
                    'deliveryPlan_ExpectedDeliveryDate'                 => Arr::get($request->payload, 'deliveryPlan_ExpectedDeliveryDate', optional($OBGYNHistory)->deliveryPlan_ExpectedDeliveryDate),
                    'followUp_Prenatal_ConsultationNo_2nd'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_2nd', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_2nd),
                    'followUp_Prenatal_DateVisit_2nd'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_2nd', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_2nd),
                    'followUp_Prenatal_AOGInWeeks_2nd'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_2nd', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_2nd),
                    'followUp_Prenatal_Weight_2nd'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_2nd', optional($OBGYNHistory)->followUp_Prenatal_Weight_2nd),
                    'followUp_Prenatal_CardiacRate_2nd'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_2nd', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_2nd),
                    'followUp_Prenatal_RespiratoryRate_2nd'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_2nd', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_2nd),
                    'followUp_Prenatal_BloodPresureSystolic_2nd'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_2nd', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_2nd),
                    'followUp_Prenatal_BloodPresureDiastolic_2nd'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_2nd', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_2nd),
                    'followUp_Prenatal_Temperature_2nd'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_2nd', optional($OBGYNHistory)->followUp_Prenatal_Temperature_2nd),
                    'followUp_Prenatal_ConsultationNo_3rd'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_3rd', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_3rd),
                    'followUp_Prenatal_DateVisit_3rd'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_3rd', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_3rd),
                    'followUp_Prenatal_AOGInWeeks_3rd'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_3rd', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_3rd),
                    'followUp_Prenatal_Weight_3rd'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_3rd', optional($OBGYNHistory)->followUp_Prenatal_Weight_3rd),
                    'followUp_Prenatal_CardiacRate_3rd'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_3rd', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_3rd),
                    'followUp_Prenatal_RespiratoryRate_3rd'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_3rd', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_3rd),
                    'followUp_Prenatal_BloodPresureSystolic_3rd'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_3rd', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_3rd),
                    'followUp_Prenatal_BloodPresureDiastolic_3rd'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_3rd', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_3rd),
                    'followUp_Prenatal_Temperature_3rd'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_3rd', optional($OBGYNHistory)->followUp_Prenatal_Temperature_3rd),
                    'followUp_Prenatal_ConsultationNo_4th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_4th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_4th),
                    'followUp_Prenatal_DateVisit_4th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_4th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_4th),
                    'followUp_Prenatal_AOGInWeeks_4th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_4th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_4th),
                    'followUp_Prenatal_Weight_4th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_4th', optional($OBGYNHistory)->followUp_Prenatal_Weight_4th),
                    'followUp_Prenatal_CardiacRate_4th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_4th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_4th),
                    'followUp_Prenatal_RespiratoryRate_4th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_4th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_4th),
                    'followUp_Prenatal_BloodPresureSystolic_4th'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_4th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_4th),
                    'followUp_Prenatal_ConsultationNo_5th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_5th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_5th),
                    'followUp_Prenatal_DateVisit_5th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_5th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_5th),
                    'followUp_Prenatal_AOGInWeeks_5th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_5th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_5th),
                    'followUp_Prenatal_Weight_5th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_5th', optional($OBGYNHistory)->followUp_Prenatal_Weight_5th),
                    'followUp_Prenatal_CardiacRate_5th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_5th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_5th),
                    'followUp_Prenatal_RespiratoryRate_5th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_5th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_5th),
                    'followUp_Prenatal_BloodPresureSystolic_5th'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_5th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_5th),
                    'followUp_Prenatal_BloodPresureDiastolic_5th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_5th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_5th),
                    'followUp_Prenatal_Temperature_5th'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_5th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_5th),
                    'followUp_Prenatal_ConsultationNo_6th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_6th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_6th),
                    'followUp_Prenatal_DateVisit_6th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_6th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_6th),
                    'followUp_Prenatal_AOGInWeeks_6th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_6th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_6th),
                    'followUp_Prenatal_Weight_6th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_6th', optional($OBGYNHistory)->followUp_Prenatal_Weight_6th),
                    'followUp_Prenatal_CardiacRate_6th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_6th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_6th),
                    'followUp_Prenatal_RespiratoryRate_6th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_6th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_6th),
                    'followUp_Prenatal_BloodPresureSystolic_6th'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_6th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_6th),
                    'followUp_Prenatal_BloodPresureDiastolic_6th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_6th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_6th),
                    'followUp_Prenatal_Temperature_6th'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_6th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_6th),
                    'followUp_Prenatal_ConsultationNo_7th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_7th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_7th),
                    'followUp_Prenatal_DateVisit_7th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_7th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_7th),
                    'followUp_Prenatal_AOGInWeeks_7th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_7th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_7th),
                    'followUp_Prenatal_Weight_7th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_7th', optional($OBGYNHistory)->followUp_Prenatal_Weight_7th),
                    'followUp_Prenatal_CardiacRate_7th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_7th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_7th),
                    'followUp_Prenatal_RespiratoryRate_7th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_7th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_7th),
                    'followUp_Prenatal_BloodPresureDiastolic_7th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_7th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_7th),
                    'followUp_Prenatal_Temperature_7th'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_7th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_7th),
                    'followUp_Prenatal_ConsultationNo_8th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_8th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_8th),
                    'followUp_Prenatal_DateVisit_8th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_8th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_8th),
                    'followUp_Prenatal_AOGInWeeks_8th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_8th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_8th),
                    'followUp_Prenatal_Weight_8th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_8th', optional($OBGYNHistory)->followUp_Prenatal_Weight_8th),
                    'followUp_Prenatal_CardiacRate_8th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_8th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_8th),
                    'followUp_Prenatal_RespiratoryRate_8th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_8th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_8th),
                    'followUp_Prenatal_BloodPresureSystolic_8th'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_8th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_8th),
                    'followUp_Prenatal_BloodPresureDiastolic_8th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_8th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_8th),
                    'followUp_Prenatal_Temperature_8th'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_8th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_8th),
                    'followUp_Prenatal_ConsultationNo_9th'              => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_9th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_8th),
                    'followUp_Prenatal_DateVisit_9th'                   => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_9th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_9th),
                    'followUp_Prenatal_AOGInWeeks_9th'                  => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_9th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_9th),
                    'followUp_Prenatal_Weight_9th'                      => Arr::get($request->payload, 'followUp_Prenatal_Weight_9th', optional($OBGYNHistory)->followUp_Prenatal_Weight_9th),
                    'followUp_Prenatal_CardiacRate_9th'                 => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_9th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_9th),
                    'followUp_Prenatal_RespiratoryRate_9th'             => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_9th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_9th),
                    'followUp_Prenatal_BloodPresureSystolic_9th'        => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_9th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_9th),
                    'followUp_Prenatal_BloodPresureDiastolic_9th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_9th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_9th),
                    'followUp_Prenatal_Temperature_9th'                 => Arr::get($request->payload, 'followUp_Prenatal_Temperature_9th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_9th),
                    'followUp_Prenatal_ConsultationNo_10th'             => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_10th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_10th),
                    'followUp_Prenatal_DateVisit_10th'                  => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_10th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_10th),
                    'followUp_Prenatal_AOGInWeeks_10th'                 => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_10th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_10th),
                    'followUp_Prenatal_Weight_10th'                     => Arr::get($request->payload, 'followUp_Prenatal_Weight_10th', optional($OBGYNHistory)->followUp_Prenatal_Weight_10th),
                    'followUp_Prenatal_CardiacRate_10th'                => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_10th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_10th),
                    'followUp_Prenatal_RespiratoryRate_10th'            => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_10th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_10th),
                    'followUp_Prenatal_BloodPresureSystolic_10th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_10th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_10th),
                    'followUp_Prenatal_BloodPresureDiastolic_10th'      => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_10th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_10th),
                    'followUp_Prenatal_Temperature_10th'                => Arr::get($request->payload, 'followUp_Prenatal_Temperature_10th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_10th),
                    'followUp_Prenatal_ConsultationNo_11th'             => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_11th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_11th),
                    'followUp_Prenatal_DateVisit_11th'                  => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_11th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_11th),
                    'followUp_Prenatal_AOGInWeeks_11th'                 => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_11th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_11th),
                    'followUp_Prenatal_Weight_11th'                     => Arr::get($request->payload, 'followUp_Prenatal_Weight_11th', optional($OBGYNHistory)->followUp_Prenatal_Weight_11th),
                    'followUp_Prenatal_CardiacRate_11th'                => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_11th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_11th),
                    'followUp_Prenatal_RespiratoryRate_11th'            => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_11th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_11th),
                    'followUp_Prenatal_BloodPresureSystolic_11th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_11th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_11th),
                    'followUp_Prenatal_BloodPresureDiastolic_11th'      => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_11th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_11th),
                    'followUp_Prenatal_Temperature_11th'                => Arr::get($request->payload, 'followUp_Prenatal_Temperature_11th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_11th),
                    'followUp_Prenatal_ConsultationNo_12th'             => Arr::get($request->payload, 'followUp_Prenatal_ConsultationNo_12th', optional($OBGYNHistory)->followUp_Prenatal_ConsultationNo_12th),
                    'followUp_Prenatal_DateVisit_12th'                  => Arr::get($request->payload, 'followUp_Prenatal_DateVisit_12th', optional($OBGYNHistory)->followUp_Prenatal_DateVisit_12th),
                    'followUp_Prenatal_AOGInWeeks_12th'                 => Arr::get($request->payload, 'followUp_Prenatal_AOGInWeeks_12th', optional($OBGYNHistory)->followUp_Prenatal_AOGInWeeks_12th),
                    'followUp_Prenatal_Weight_12th'                     => Arr::get($request->payload, 'ffollowUp_Prenatal_Weight_12th', optional($OBGYNHistory)->ffollowUp_Prenatal_Weight_12th),
                    'followUp_Prenatal_CardiacRate_12th'                => Arr::get($request->payload, 'followUp_Prenatal_CardiacRate_12th', optional($OBGYNHistory)->followUp_Prenatal_CardiacRate_12th),
                    'followUp_Prenatal_RespiratoryRate_12th'            => Arr::get($request->payload, 'followUp_Prenatal_RespiratoryRate_12th', optional($OBGYNHistory)->followUp_Prenatal_RespiratoryRate_12th),
                    'followUp_Prenatal_BloodPresureSystolic_12th'       => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureSystolic_12th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureSystolic_12th),
                    'followUp_Prenatal_BloodPresureDiastolic_12th'      => Arr::get($request->payload, 'followUp_Prenatal_BloodPresureDiastolic_12th', optional($OBGYNHistory)->followUp_Prenatal_BloodPresureDiastolic_12th),
                    'followUp_Prenatal_Temperature_12th'                => Arr::get($request->payload, 'followUp_Prenatal_Temperature_12th', optional($OBGYNHistory)->followUp_Prenatal_Temperature_12th),
                    'followUp_Prenatal_Remarks'                         => Arr::get($request->payload, 'followUp_Prenatal_Remarks', optional($OBGYNHistory)->followUp_Prenatal_Remarks),
                    'updatedBy'                                         => $checkUser->idnumber,
                    'updated_at'                                        => $currentTimestamp
                ];

                $patientPregnancyHistoryData = [
                    'pregnancyNumber'   => $registry_id,
                    'outcome'           => Arr::get($request->payload, 'outcome', optional($pregnancyHistory)->outcome),
                    'deliveryDate'      => Arr::get($request->payload, 'deliveryDate', optional($pregnancyHistory)->deliveryDate),
                    'complications'     => Arr::get($request->payload, 'complications', optional($pregnancyHistory)->complications),
                    'updatedBy'         => $checkUser->idnumber,
                    'updated_at'        => $currentTimestamp,
                ];

                $patientGynecologicalConditions = [
                    'conditionName'     => $registry_id,
                    'diagnosisDate'     => Arr::get($request->payload, 'diagnosisDate', optional($gynecologicalConditions)->diagnosisDate),
                    'updatedBy'         => $checkUser->idnumber,
                    'updated_at'        => $currentTimestamp,
                ];

                $patientMedicationsData = [
                    'item_Id'               => Arr::get($request->payload, 'item_Id', optional($medications)->item_Id),
                    'drug_Description'      => Arr::get($request->payload, 'drug_Description', optional($medications)->drug_Description),
                    'dosage'                => Arr::get($request->payload, 'dosage', optional($medications)->dosage),
                    'reason_For_Use'        => Arr::get($request->payload, 'reason_For_Use', optional($medications)->reason_For_Use),
                    'adverse_Side_Effect'   => Arr::get($request->payload, 'adverse_Side_Effect', optional($medications)->adverse_Side_Effect),
                    'hospital'              => Arr::get($request->payload, 'hospital', optional($medications)->hospital),
                    'isPrescribed'          => Arr::get($request->payload, 'isPrescribed', optional($medications)->isPrescribed),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientDischargeInstructions = [
                    'branch_Id'                         => Arr::get($request->payload, 'branch_Id', optional($dischargeInstructions)->branch_Id),
                    'general_Instructions'              => Arr::get($request->payload, 'general_Intructions', optional($dischargeInstructions)->general_Instructions),
                    'dietary_Instructions'              => Arr::get($request->payload, 'dietary_Intructions', optional($dischargeInstructions)->dietary_Instructions),
                    'medications_Instructions'          => Arr::get($request->payload, 'medications_Intructions', optional($dischargeInstructions)->medications_Instructions),
                    'activity_Restriction'              => Arr::get($request->payload, 'activity_Restriction', optional($dischargeInstructions)->activity_Restriction),
                    'dietary_Restriction'               => Arr::get($request->payload, 'dietary_Restriction', optional($dischargeInstructions)->dietary_Restriction),
                    'addtional_Notes'                   => Arr::get($request->payload, 'addtional_Notes', optional($dischargeInstructions)->addtional_Notes),
                    'clinicalPharmacist_OnDuty'         => Arr::get($request->payload, 'clinicalPharmacist_OnDuty', optional($dischargeInstructions)->clinicalPharmacist_OnDuty),
                    'clinicalPharmacist_CheckTime'      => Arr::get($request->payload, 'clinicalPharmacist_CheckTime', optional($dischargeInstructions)->clinicalPharmacist_CheckTime),
                    'nurse_OnDuty'                      => Arr::get($request->payload, 'nurse_OnDuty', optional($dischargeInstructions)->nurse_OnDuty),
                    'intructedBy_clinicalPharmacist'    => Arr::get($request->payload, 'intructedBy_clinicalPharmacist', optional($dischargeInstructions)->intructedBy_clinicalPharmacist),
                    'intructedBy_Dietitians'            => Arr::get($request->payload, 'intructedBy_Dietitians', optional($dischargeInstructions)->intructedBy_Dietitians),
                    'intructedBy_Nurse'                 => Arr::get($request->payload, 'intructedBy_Nurse', optional($dischargeInstructions)->intructedBy_Nurse),
                    'updatedBy'                         => $checkUser->idnumber,
                    'updated_at'                        => $currentTimestamp,
                ];

                $patientDischargeMedications = [
                    'Item_Id'               => Arr::get($request->payload, 'Item_Id', optional($dischargeMedications)->Item_Id),
                    'medication_Name'       => Arr::get($request->payload, 'medication_Name', optional($dischargeMedications)->medication_Name),
                    'medication_Type'       => Arr::get($request->payload, 'medication_Type', optional($dischargeMedications)->medication_Type),
                    'dosage'                => Arr::get($request->payload, 'dosage', optional($dischargeMedications)->dosage),
                    'frequency'             => Arr::get($request->payload, 'frequency', optional($dischargeMedications)->frequency),
                    'purpose'               => Arr::get($request->payload, 'purpose', optional($dischargeMedications)->purpose),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientDischargeFollowUpTreatment = [
                    'treatment_Description' => Arr::get($request->payload, 'treatment_Description', optional($dischargeFollowUpTreatment)->treatment_Description),
                    'treatment_Date'        => Arr::get($request->payload, 'treatment_Date', optional($dischargeFollowUpTreatment)->treatment_Date),
                    'doctor_Id'             => Arr::get($request->payload, 'doctor_Id', optional($dischargeFollowUpTreatment)->doctor_Id),
                    'doctor_Name'           => Arr::get($request->payload, 'doctor_Name', optional($dischargeFollowUpTreatment)->doctor_Name),
                    'notes'                 => Arr::get($request->payload, 'notes', optional($dischargeFollowUpTreatment)->notes),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientDischargeFollowUpLaboratories = [
                    'item_Id'           => Arr::get($request->payload, 'item_Id', optional($dischargeFollowUpLaboratories)->item_Id),
                    'test_Name'         => Arr::get($request->payload, 'test_Name', optional($dischargeFollowUpLaboratories)->test_Name),
                    'test_DateTime'     => Arr::get($request->payload, 'test_DateTime', optional($dischargeFollowUpLaboratories)->test_DateTime),
                    'notes'             => Arr::get($request->payload, 'notes', optional($dischargeFollowUpLaboratories)->notes),
                    'updatedBy'         => $checkUser->idnumber,
                    'updated_at'        => $currentTimestamp,
                ];

                $patientDischargeDoctorsFollowUp = [
                    'doctor_Id'             => Arr::get($request->payload, 'doctor_Id', optional($dischargeDoctorsFollowUp)->doctor_Id),
                    'doctor_Name'           => Arr::get($request->payload, 'doctor_Name', optional($dischargeDoctorsFollowUp)->doctor_Name),
                    'doctor_Specialization' => Arr::get($request->payload, 'doctor_Specialization', optional($dischargeDoctorsFollowUp)->doctor_Specialization),
                    'schedule_Date'         => Arr::get($request->payload, 'schedule_Date', optional($dischargeDoctorsFollowUp)->schedule_Date),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,
                ];

                $patientHistoryData = [
                    'branch_Id'                                 => Arr::get($request->payload, 'branch_Id', 1),
                    'brief_History'                             => Arr::get($request->payload, 'brief_History', optional($patientHistory)->brief_History),
                    'pastMedical_History'                       => Arr::get($request->payload, 'pastMedical_History', optional($patientHistory)->pastMedical_History),
                    'family_History'                            => Arr::get($request->payload, 'family_History', optional($patientHistory)->family_History),
                    'personalSocial_History'                    => Arr::get($request->payload, 'personalSocial_History', optional($patientHistory)->personalSocial_History),
                    'chief_Complaint_Description'               => Arr::get($request->payload, 'chief_Complaint_Description', optional($patientHistory)->chief_Complaint_Description),
                    'impression'                                => Arr::get($request->payload, 'impression', optional($patientHistory)->impression),
                    'admitting_Diagnosis'                       => Arr::get($request->payload, 'admitting_Diagnosis', optional($patientHistory)->admitting_Diagnosis),
                    'discharge_Diagnosis'                       => Arr::get($request->payload, 'discharge_Diagnosis', optional($patientHistory)->discharge_Diagnosis),
                    'preOperative_Diagnosis'                    => Arr::get($request->payload, 'preOperative_Diagnosis', optional($patientHistory)->preOperative_Diagnosis),
                    'postOperative_Diagnosis'                   => Arr::get($request->payload, 'postOperative_Diagnosis', optional($patientHistory)->postOperative_Diagnosis),
                    'surgical_Procedure'                        => Arr::get($request->payload, 'surgical_Procedure', optional($patientHistory)->surgical_Procedure),
                    'physicalExamination_Skin'                  => Arr::get($request->payload, 'physicalExamination_Skin', optional($patientHistory)->physicalExamination_Skin),
                    'physicalExamination_HeadEyesEarsNeck'      => Arr::get($request->payload, 'physicalExamination_HeadEyesEarsNeck', optional($patientHistory)->physicalExamination_HeadEyesEarsNeck),
                    'physicalExamination_Neck'                  => Arr::get($request->payload, 'physicalExamination_Neck', optional($patientHistory)->physicalExamination_Neck),
                    'physicalExamination_ChestLungs'            => Arr::get($request->payload, 'physicalExamination_ChestLungs', optional($patientHistory)->physicalExamination_ChestLungs),
                    'physicalExamination_CardioVascularSystem'  => Arr::get($request->payload, 'physicalExamination_CardioVascularSystem', optional($patientHistory)->physicalExamination_CardioVascularSystem),
                    'physicalExamination_Abdomen'               => Arr::get($request->payload, 'physicalExamination_Abdomen', optional($patientHistory)->physicalExamination_Abdomen),
                    'physicalExamination_GenitourinaryTract'    => Arr::get($request->payload, 'physicalExamination_GenitourinaryTract', optional($patientHistory)->physicalExamination_GenitourinaryTract),
                    'physicalExamination_Rectal'                => Arr::get($request->payload, 'physicalExamination_Rectal', optional($patientHistory)->physicalExamination_Rectal),
                    'physicalExamination_Musculoskeletal'       => Arr::get($request->payload, 'physicalExamination_Musculoskeletal', optional($patientHistory)->physicalExamination_Musculoskeletal),
                    'physicalExamination_LympNodes'             => Arr::get($request->payload, 'physicalExamination_LympNodes', optional($patientHistory)->physicalExamination_LympNodes),
                    'physicalExamination_Extremities'           => Arr::get($request->payload, 'physicalExamination_Extremities', optional($patientHistory)->physicalExamination_Extremities),
                    'physicalExamination_Neurological'          => Arr::get($request->payload, 'physicalExamination_Neurological', optional($patientHistory)->physicalExamination_Neurological),
                    'updatedBy'                                 => $checkUser->idnumber,
                    'updated_at'                                => $currentTimestamp,
                ];

                $patientMedicalProcedureData = [
                    'description'                   => Arr::get($request->payload, 'description', optional($patientMedicalProcedure)->description),
                    'date_Of_Procedure'             => Arr::get($request->payload, 'date_Of_Procedure', optional($patientMedicalProcedure)->date_Of_Procedure),
                    'performing_Doctor_Id'          => Arr::get($request->payload, 'performing_Doctor_Id', optional($patientMedicalProcedure)->performing_Doctor_Id),
                    'performing_Doctor_Fullname'    => Arr::get($request->payload, 'performing_Doctor_Fullname', optional($patientMedicalProcedure)->performing_Doctor_Fullname),
                    'updatedBy'                     => $checkUser->idnumber,
                    'updated_at'                    => $currentTimestamp,
                ];

                $patientVitalSignsData = [
                    'branch_Id'                 => 1,
                    'transDate'                 => $today,
                    'bloodPressureSystolic'     => (int) Arr::get($request->payload, 'bloodPressureSystolic', optional($patientVitalSign)->bloodPressureSystolic),
                    'bloodPressureDiastolic'    => (int) Arr::get($request->payload, 'bloodPressureDiastolic', optional($patientVitalSign)->bloodPressureDiastolic),
                    'temperature'               => (int) Arr::get($request->payload, 'temperature', optional($patientVitalSign)->temperature),
                    'pulseRate'                 => (int) Arr::get($request->payload, 'pulseRate', optional($patientVitalSign)->pulseRate),
                    'respiratoryRate'           => (int) Arr::get($request->payload, 'respiratoryRate', optional($patientVitalSign)->respiratoryRate),
                    'oxygenSaturation'          => (float) Arr::get($request->payload, 'oxygenSaturation', optional($patientVitalSign)->oxygenSaturation),
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,
                ];
        
                $patientRegistryData = [
                    'branch_Id'                                 =>  1,
                    'er_Case_No'                                => $er_Case_No, // Arr::get($request->payload, 'er_Case_No', optional($patientRegistry)->er_Case_No),
                    'register_source'                           => Arr::get($request->payload, 'register_Source', optional($patientRegistry)->register_Source),
                    'register_Casetype'                         => Arr::get($request->payload, 'register_Casetype', optional($patientRegistry)->register_Casetype),
                    'register_Link_Case_No'                     => Arr::get($request->payload, 'register_Link_Case_No', optional($patientRegistry)->register_Link_Case_No),
                    'register_Case_No_Consolidate'              => Arr::get($request->payload, 'register_Case_No_Consolidate', optional($patientRegistry)->register_Case_No_Consolidate),
                    'patient_Age'                               => Arr::get($request->payload, 'age', optional($patientRegistry)->patient_Age),
                    'er_Bedno'                                  => Arr::get($request->payload, 'er_Bedno', optional($patientRegistry)->er_Bedno),
                    'room_Code'                                 => Arr::get($request->payload, 'room_Code', optional($patientRegistry)->room_Code),
                    'room_Rate'                                 => (float)Arr::get($request->payload, 'room_Rate', optional($patientRegistry)->room_Rate),
                    'mscAccount_Type'                           => Arr::get($request->payload, 'mscAccount_Type', optional($patientRegistry)->mscAccount_Type),
                    'mscAccount_Discount_Id'                    => (int)Arr::get($request->payload, 'mscAccount_Discount_Id', optional($patientRegistry)->mscAccount_Discount_Id),
                    'mscAccount_Trans_Types'                    => Arr::get($request->payload, 'mscAccount_Trans_Types', optional($patientRegistry)->mscAccount_Trans_Types), 
                    'mscAdmission_Type_Id'                      => Arr::get($request->payload, 'mscAdmission_Type_Id', optional($patientRegistry)->mscAdmission_Type_Id),
                    'mscPatient_Category'                       => (int)Arr::get($request->payload, 'mscPatient_Category', optional($patientRegistry)->mscPatient_Category),
                    'mscPrice_Groups'                           => (int)Arr::get($request->payload, 'mscPrice_Groups', optional($patientRegistry)->mscPrice_Groups),
                    'mscPrice_Schemes'                          => (int)Arr::get($request->payload, 'mscPrice_Schemes', optional($patientRegistry)->mscPrice_Schemes),
                    'mscService_Type'                           => (int)Arr::get($request->payload, 'mscService_Type', optional($patientRegistry)->mscService_Type),
                    'mscService_Type2'                          => (int)Arr::get($request->payload, 'mscService_Type2', optional($patientRegistry)->mscService_Type2),
                    'mscDiet_Meal_Id'                           => (int)Arr::get($request->payload, 'mscDiet_Meal_Id', optional($patientRegistry)->mscDiet_Meal_Id),
                    'mscDisposition_Id'                         => (int)Arr::get($request->payload, 'mscDisposition_Id', optional($patientRegistry)->mscDisposition_Id),
                    'mscTriage_level_Id'                        => (int)Arr::get($request->payload, 'mscTriage_level_Id', optional($patientRegistry)->mscTriage_level_Id),
                    'mscCase_Result_Id'                         => (int)Arr::get($request->payload, 'mscCase_Result_Id', optional($patientRegistry)->mscCase_Result_Id),
                    'mscCase_Indicators_Id'                     => (int)Arr::get($request->payload, 'mscCase_Indicators_Id', optional($patientRegistry)->mscCase_Indicators_Id),
                    'mscPrivileged_Card_Id'                     => (int)Arr::get($request->payload, 'mscPrivileged_Card_Id', optional($patientRegistry)->mscPrivileged_Card_Id),
                    'mscBroughtBy_Relationship_Id'              => (int)Arr::get($request->payload, 'mscBroughtBy_Relationship_Id', optional($patientRegistry)->mscBroughtBy_Relationship_Id),
                    'queue_Number'                              => Arr::get($request->payload, 'queue_Number', optional($patientRegistry)->queue_Number),
                    'arrived_Date'                              => $currentTimestamp,
                    'registry_Userid'                           => $checkUser->idnumber,
                    'registry_Date'                             => $currentTimestamp,
                    'registry_Status'                           => 1,
                    'registry_Hostname'                         => (new GetIP())->getHostname() ?? optional($patientRegistry)->registry_Hostname,
                    'discharged_Userid'                         => Arr::get($request->payload, 'discharged_Userid', optional($patientRegistry)->discharged_Userid),
                    'discharged_Date'                           => Arr::get($request->payload, 'discharged_Date', optional($patientRegistry)->discharged_Date),
                    'discharged_Hostname'                       => Arr::get($request->payload, 'discharged_Hostname', optional($patientRegistry)->discharged_Hostname),
                    'billed_Userid'                             => Arr::get($request->payload, 'billed_Userid', optional($patientRegistry)->billed_Userid),
                    'billed_Date'                               => Arr::get($request->payload, 'billed_Date', optional($patientRegistry)->billed_Date),
                    'billed_Remarks'                            => Arr::get($request->payload, 'billed_Remarks', optional($patientRegistry)->billed_Remarks),
                    'billed_Hostname'                           => Arr::get($request->payload, 'billed_Hostname', optional($patientRegistry)->billed_Hostname),
                    'mgh_Userid'                                => Arr::get($request->payload, 'mgh_Userid', optional($patientRegistry)->mgh_Userid),
                    'mgh_Datetime'                              => Arr::get($request->payload, 'mgh_Datetime', optional($patientRegistry)->mgh_Datetime),
                    'mgh_Hostname'                              => Arr::get($request->payload, 'mgh_Hostname', optional($patientRegistry)->mgh_Hostname),
                    'untag_Mgh_Userid'                          => Arr::get($request->payload, 'untag_Mgh_Userid', optional($patientRegistry)->untag_Mgh_Userid),
                    'untag_Mgh_Datetime'                        => Arr::get($request->payload, 'untag_Mgh_Datetime', optional($patientRegistry)->untag_Mgh_Datetime),
                    'untag_Mgh_Hostname'                        => Arr::get($request->payload, 'untag_Mgh_Hostname', optional($patientRegistry)->untag_Mgh_Hostname),
                    'isHoldReg'                                 => Arr::get($request->payload, 'isHoldReg', false),
                    'hold_Userid'                               => Arr::get($request->payload, 'hold_Userid', optional($patientRegistry)->hold_Userid),
                    'hold_No'                                   => Arr::get($request->payload, 'hold_No', optional($patientRegistry)->hold_No),
                    'hold_Date'                                 => Arr::get($request->payload, 'hold_Date', optional($patientRegistry)->hold_Date),
                    'hold_Remarks'                              => Arr::get($request->payload, 'hold_Remarks', optional($patientRegistry)->hold_Remarks),
                    'hold_Hostname'                             => Arr::get($request->payload, 'hold_Hostname', optional($patientRegistry)->hold_Hostname),
                    'isRevoked'                                 => Arr::get($request->payload, 'isRevoked', false),
                    'revokedBy'                                 => Arr::get($request->payload, 'revokedBy', optional($patientRegistry)->revokedBy),
                    'revoked_Date'                              => Arr::get($request->payload, 'revoked_Date', optional($patientRegistry)->revoked_Date),
                    'revoked_Remarks'                           => Arr::get($request->payload, 'revoked_Remarks', optional($patientRegistry)->revoked_Remarks),
                    'revoked_Hostname'                          => Arr::get($request->payload, 'revoked_Hostname', optional($patientRegistry)->revoked_Hostname),
                    'dischargeNotice_Userid'                    => Arr::get($request->payload, 'dischargeNotice_Userid', optional($patientRegistry)->dischargeNotice_Userid),
                    'dischargeNotice_Date'                      => Arr::get($request->payload, 'dischargeNotice_Date', optional($patientRegistry)->dischargeNotice_Date),
                    'dischargeNotice_Hostname'                  => Arr::get($request->payload, 'dischargeNotice_Hostname', optional($patientRegistry)->dischargeNotice_Hostname),
                    'hbps_PrintedBy'                            => Arr::get($request->payload, 'hbps_PrintedBy', optional($patientRegistry)->hbps_PrintedBy),
                    'hbps_Date'                                 => Arr::get($request->payload, 'hbps_Date', optional($patientRegistry)->hbps_Date),
                    'hbps_Hostname'                             => Arr::get($request->payload, 'hbps_Hostname', optional($patientRegistry)->hbps_Hostname),
                    'informant_Lastname'                        => Arr::get($request->payload, 'informant_Lastname', optional($patientRegistry)->informant_Lastname),
                    'informant_Firstname'                       => Arr::get($request->payload, 'informant_Firstname', optional($patientRegistry)->informant_Firstname),
                    'informant_Middlename'                      => Arr::get($request->payload, 'informant_Middlename', optional($patientRegistry)->informant_Middlename),
                    'informant_Suffix'                          => Arr::get($request->payload, 'informant_Suffix', optional($patientRegistry)->informant_Suffix),
                    'informant_Address'                         => Arr::get($request->payload, 'informant_Address', optional($patientRegistry)->informant_Address),
                    'informant_Relation_id'                     => Arr::get($request->payload, 'informant_Relation_id', optional($patientRegistry)->informant_Relation_id),
                    'guarantor_Id'                              => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_code', optional($patientRegistry)->guarantor_Id),
                    'guarantor_Name'                            => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_name', optional($patientRegistry)->guarantor_Name),
                    'guarantor_Approval_code'                   => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_approval_code', optional($patientRegistry)->guarantor_Approval_code),
                    'guarantor_Approval_no'                     => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_approval_no', optional($patientRegistry)->guarantor_Approval_no),
                    'guarantor_Approval_date'                   => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_approval_date', optional($patientRegistry)->guarantor_Approval_date),
                    'guarantor_Validity_date'                   => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_validity_date', optional($patientRegistry)->guarantor_Validity_date),
                    'guarantor_Approval_remarks'                => Arr::get($request->payload, 'guarantor_Approval_remarks', optional($patientRegistry)->guarantor_Approval_remarks),
                    'isWithCreditLimit'                         => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_code') ? Arr::get($request->payload, 'isWithCreditLimit', false) : false,
                    'guarantor_Credit_Limit'                    => Arr::get($request->payload, 'selectedGuarantor.0.guarantor_credit_Limit', optional($patientRegistry)->guarantor_Credit_Limit),
                    'isWithMultiple_Gurantor'                   => Arr::get($request->payload, 'isWithMultiple_Gurantor', false),
                    'gurantor_Mutiple_TotalCreditLimit'         => Arr::get($request->payload, 'gurantor_Mutiple_TotalCreditLimit', false),
                    'isWithPhilHealth'                          => Arr::get($request->payload, 'isWithPhilHealth', false),
                    'mscPHIC_Membership_Type_id'                => Arr::get($request->payload, 'mscPHIC_Membership_Type_id', optional($patientRegistry)->mscPHIC_Membership_Type_id),
                    'philhealth_Number'                         => Arr::get($request->payload, 'philhealth_Number', optional($patientRegistry)->philhealth_Number),
                    'isWithMedicalPackage'                      => Arr::get($request->payload, 'isWithMedicalPackage', false),
                    'medical_Package_Id'                        => Arr::get($request->payload, 'medical_Package_Id', optional($patientRegistry)->medical_Package_Id),
                    'medical_Package_Name'                      => Arr::get($request->payload, 'medical_Package_Name', optional($patientRegistry)->medical_Package_Name),
                    'medical_Package_Amount'                    => Arr::get($request->payload, 'medical_Package_Amount', optional($patientRegistry)->medical_Package_Amount),
                    'chief_Complaint_Description'               => Arr::get($request->payload, 'chief_Complaint_Description', optional($patientRegistry)->chief_Complaint_Description),
                    'impression'                                => Arr::get($request->payload, 'impression', optional($patientRegistry)->impression),
                    'admitting_Diagnosis'                       => Arr::get($request->payload, 'admitting_Diagnosis', optional($patientRegistry)->admitting_Diagnosis),
                    'discharge_Diagnosis'                       => Arr::get($request->payload, 'discharge_Diagnosis', optional($patientRegistry)->discharge_Diagnosis),
                    'preOperative_Diagnosis'                    => Arr::get($request->payload, 'preOperative_Diagnosis', optional($patientRegistry)->preOperative_Diagnosis),
                    'postOperative_Diagnosis'                   => Arr::get($request->payload, 'postOperative_Diagnosis', optional($patientRegistry)->postOperative_Diagnosis),
                    'surgical_Procedure'                        => Arr::get($request->payload, 'surgical_Procedure', optional($patientRegistry)->surgical_Procedure),
                    'triageNotes'                               => Arr::get($request->payload, 'triageNotes', optional($patientRegistry)->triageNotes),
                    'triageDate'                                => Arr::get($request->payload, 'triageDate', optional($patientRegistry)->triageDate),
                    'isCriticallyIll'                           => Arr::get($request->payload, 'isCriticallyIll', false),
                    'illness_Type'                              => Arr::get($request->payload, 'illness_Type', optional($patientRegistry)->illness_Type),
                    'attending_Doctor'                          => Arr::get($request->payload, 'selectedConsultant.0.attending_Doctor', optional($patientRegistry)->attending_Doctor),
                    'attending_Doctor_fullname'                 => Arr::get($request->payload, 'selectedConsultant.0.attending_Doctor_fullname', optional($patientRegistry)->attending_Doctor_fullname),
                    'bmi'                                       => Arr::get($request->payload, 'bmi', optional($patientRegistry)->bmi),
                    'weight'                                    => Arr::get($request->payload, 'weight', optional($patientRegistry)->weight),
                    'weightUnit'                                => Arr::get($request->payload, 'weightUnit', optional($patientRegistry)->weightUnit),
                    'height'                                    => Arr::get($request->payload, 'height', optional($patientRegistry)->height),
                    'heightUnit'                                => Arr::get($request->payload, 'heightUnit', optional($patientRegistry)->heightUnit),
                    'bloodPressureSystolic'                     => Arr::get($request->payload, 'bloodPressureSystolic', optional($patientRegistry)->bloodPressureSystolic),
                    'bloodPressureDiastolic'                    => Arr::get($request->payload, 'bloodPressureDiastolic', optional($patientRegistry)->bloodPressureDiastolic),
                    'temperatute'                               => Arr::get($request->payload, 'temperature', optional($patientRegistry)->temperatute),
                    'pulseRate'                                 => Arr::get($request->payload, 'pulseRate', optional($patientRegistry)->pulseRate),
                    'respiratoryRate'                           => Arr::get($request->payload, 'respiratoryRate', optional($patientRegistry)->respiratoryRate),
                    'oxygenSaturation'                          => Arr::get($request->payload, 'oxygenSaturation', optional($patientRegistry)->oxygenSaturation),
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
                    'typeOfBirth_id'                            => Arr::get($request->payload, 'typeOfBirth_id', optional($patientRegistry)->typeOfBirth_id),
                    'isWithBaby'                                => Arr::get($request->payload, 'isWithBaby', optional($patientRegistry)->isWithBaby),
                    'isRoomIn'                                  => Arr::get($request->payload, 'isRoomIn', optional($patientRegistry)->isRoomIn),
                    'birthDate'                                 => Arr::get($request->payload, 'birthDate', optional($patientRegistry)->birthDate),
                    'birthTime'                                 => Arr::get($request->payload, 'birthTime', optional($patientRegistry)->birthTime),
                    'newborn_Status_Id'                         => Arr::get($request->payload, 'newborn_Status_Id', optional($patientRegistry)->newborn_Status_Id),
                    'mother_Case_No'                            => Arr::get($request->payload, 'mother_Case_No', optional($patientRegistry)->mother_Case_No),
                    'isDiedLess48Hours'                         => Arr::get($request->payload, 'isDiedLess48Hours', optional($patientRegistry)->isDiedLess48Hours),
                    'isDeadOnArrival'                           => Arr::get($request->payload, 'isDeadOnArrival', optional($patientRegistry)->isDeadOnArrival),
                    'isAutopsy'                                 => Arr::get($request->payload, 'isAutopsy', optional($patientRegistry)->isAutopsy),
                    'typeOfDeath_id'                            => Arr::get($request->payload, 'typeOfDeath_id', optional($patientRegistry)->typeOfDeath_id),
                    'dateOfDeath'                               => Arr::get($request->payload, 'dateOfDeath', optional($patientRegistry)->dateOfDeath),
                    'timeOfDeath'                               => Arr::get($request->payload, 'timeOfDeath', optional($patientRegistry)->timeOfDeath),
                    'barcode_Image'                             => Arr::get($request->payload, 'barcode_Image', optional($patientRegistry)->barcode_Image),
                    'barcode_Code_Id'                           => Arr::get($request->payload, 'barcode_Code_Id', optional($patientRegistry)->barcode_Code_Id),
                    'barcode_Code_String'                       => Arr::get($request->payload, 'barcode_Code_String', optional($patientRegistry)->barcode_Code_String),
                    'isreferredFrom'                            => Arr::get($request->payload, 'isreferredFrom', false),
                    'referred_From_HCI'                         => Arr::get($request->payload, 'referred_From_HCI', optional($patientRegistry)->referred_From_HCI),
                    'referred_From_HCI_address'                 => Arr::get($request->payload, 'FromHCIAddress', optional($patientRegistry)->referred_From_HCI_address),
                    'referred_From_HCI_code'                    => Arr::get($request->payload, 'referred_From_HCI_code', optional($patientRegistry)->referred_From_HCI_code),
                    'referred_To_HCI'                           => Arr::get($request->payload, 'referred_To_HCI', optional($patientRegistry)->referred_To_HCI),
                    'referred_To_HCI_code'                      => Arr::get($request->payload, 'referred_To_HCI_code', optional($patientRegistry)->referred_To_HCI_code),
                    'referred_To_HCI_address'                   => Arr::get($request->payload, 'ToHCIAddress', optional($patientRegistry)->referred_To_HCI_address),
                    'referring_Doctor'                          => Arr::get($request->payload, 'referring_Doctor', optional($patientRegistry)->referring_Doctor),
                    'referral_Reason'                           => Arr::get($request->payload, 'referral_Reason', optional($patientRegistry)->referral_Reason),
                    'isWithConsent_DPA'                         => Arr::get($request->payload, 'isWithConsent_DPA', optional($patientRegistry)->isWithConsent_DPA),
                    'isConfidentialPatient'                     => Arr::get($request->payload, 'isConfidentialPatient', optional($patientRegistry)->isConfidentialPatient),
                    'isMedicoLegal'                             => Arr::get($request->payload, 'isMedicoLegal', optional($patientRegistry)->isMedicoLegal),
                    'isFinalBill'                               => Arr::get($request->payload, 'isFinalBill', optional($patientRegistry)->isFinalBill),
                    'isWithPromissoryNote'                      => Arr::get($request->payload, 'isWithPromissoryNote', optional($patientRegistry)->isWithPromissoryNote),
                    'isFirstNotice'                             => Arr::get($request->payload, 'isFirstNotice', optional($patientRegistry)->isFirstNotice),
                    'FirstNoteDate'                             => Arr::get($request->payload, 'FirstNoteDate', optional($patientRegistry)->FirstNoteDate),
                    'isSecondNotice'                            => Arr::get($request->payload, 'isSecondNotice', optional($patientRegistry)->isSecondNotice),
                    'SecondNoticeDate'                          => Arr::get($request->payload, 'SecondNoticeDate', optional($patientRegistry)->SecondNoticeDate),
                    'isFinalNotice'                             => Arr::get($request->payload, 'isFinalNotice', optional($patientRegistry)->isFinalNotice),
                    'FinalNoticeDate'                           => Arr::get($request->payload, 'FinalNoticeDate', optional($patientRegistry)->FinalNoticeDate),
                    'isOpenLateCharges'                         => Arr::get($request->payload, 'isOpenLateCharges', optional($patientRegistry)->isOpenLateCharges),
                    'isBadDebt'                                 => Arr::get($request->payload, 'isBadDebt', optional($patientRegistry)->isBadDebt),
                    'registry_Remarks'                          => Arr::get($request->payload, 'registry_Remarks', optional($patientRegistry)->registry_Remarks),
                    'medsys_map_idnum'                          => Arr::get($request->payload, 'medsys_map_idnum', optional($patientRegistry)->medsys_map_idnum),
                    'updatedBy'                                 => $checkUser->idnumber,
                    'updated_at'                                => $currentTimestamp,      
                ];

                $patientImmunizationsData = [
                    'branch_id'             => 1,
                    'vaccine_Id'            => 1,
                    'administration_Date'   => Arr::get($request->payload, 'administration_Date', optional($patientImmunization)->administration_Date),
                    'dose'                  => Arr::get($request->payload, 'dose', optional($patientImmunization)->dose),
                    'site'                  => Arr::get($request->payload, 'site', optional($patientImmunization)->site),
                    'administrator_Name'    => Arr::get($request->payload, 'administrator_Name', optional($patientImmunization)->administrator_Name),
                    'Notes'                 => Arr::get($request->payload, 'Notes', optional($patientImmunization)->Notes),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,       
                ];

                $patientAdministeredMedicineData = [
                    'item_Id'               => Arr::get($request->payload, 'item_Id', optional($patientAdministeredMedicine)->item_Id),
                    'quantity'              => Arr::get($request->payload, 'quantity', optional($patientAdministeredMedicine)->quantity),
                    'administered_Date'     => Arr::get($request->payload, 'administered_Date', optional($patientAdministeredMedicine)->administered_Date),
                    'administered_By'       => Arr::get($request->payload, 'administered_By', optional($patientAdministeredMedicine)->administered_By),
                    'reference_num'         => Arr::get($request->payload, 'reference_num', optional($patientAdministeredMedicine)->reference_num),
                    'transaction_num'       => Arr::get($request->payload, 'transaction_num', optional($patientAdministeredMedicine)->transaction_num),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => $currentTimestamp,   
                ];

                
                if($existingRegistry) {
                    $pastImmunization->update($patientPastImmunizationData);
                    $pastMedicalHistory->update($patientPastMedicalHistoryData);
                    $pastMedicalProcedure->update($pastientPastMedicalProcedureData);
                    $pastBadHabits->update($patientPastBadHabitsData);
                    
                    $patientRegistry->update($patientRegistryData);
                    $patientHistory->update($patientHistoryData);
                    $patientMedicalProcedure->update($patientMedicalProcedureData);
                    $patientVitalSign->update($patientVitalSignsData);
                    $patientImmunization->update($patientImmunizationsData);
                    $patientAdministeredMedicine->update($patientAdministeredMedicineData);

                    $OBGYNHistory->update($patientOBGYNHistory);
                    $pregnancyHistory->update($patientPregnancyHistoryData);
                    $gynecologicalConditions->update($patientGynecologicalConditions);

                    if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {

                        $isDeleted = $this->updateAllergy($registry_id);

                        if($isDeleted) {
                            
                            foreach ($request->payload['selectedAllergy'] as $allergy) {

                                $commonData = [
                                    'patient_Id'            => $patient_id,
                                    'case_No'               => $registry_id,
                                    'allergy_Type_Id'       => $allergy['allergy_id'],
                                    'createdby'             => $allergy->createdby,
                                    'created_at'            => $allergy->created_at,
                                    'updatedby'             => $checkUser->idnumber,
                                    'updated_at'            => Carbon::now(),
                                    'isDeleted'             => 0,
                                ];
                            
                                $patientAllergyData = array_merge($commonData, [
                                    'allergy_description'   => $allergy['allergy_name'] ?? null,
                                    'family_History'        => $request->payload['family_History'] ?? null,
                                ]);
                        
                                $patientAllergy = $patientRegistry->allergies()->create($patientAllergyData);
                                $last_inserted_id = $patientAllergy->id;
                            
                                $patientCauseAllergyData = [
                                    'assessID'          => $last_inserted_id,
                                    'description'       => $allergy['cause'],
                                    'duration'          => $request->payload['duration'] ?? null,
                                ];
                            
                                $patientAllergy->cause_of_allergy()->create(array_merge($commonData, $patientCauseAllergyData));
                        
                                if (!empty($allergy['symptoms']) && is_array($allergy['symptoms'])) {
                                    $symptomsData = [];
                                    foreach ($allergy['symptoms'] as $symptom) {
                                        $symptomsData[] = array_merge($commonData, [
                                            'assessID'              => $last_inserted_id,
                                            'symptom_id'            => $symptom['id'],
                                            'symptom_Description'   => $symptom['description'] ?? null,
                                        ]);
                                    }
                                    $patientAllergy->symptoms_allergy()->insert($symptomsData);
                                }

                                $patientDrugUsedForAllergyData = [
                                    'assessID'          => $last_inserted_id,
                                    'drug_Description'  => $request->payload['drug_Description'] ?? null,
                                ];

                                $patient->drug_used_for_allergy()->create(array_merge($commonData, $patientDrugUsedForAllergyData));
                            }
                        } 
                    }
            
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

                    $dischargeInstructions->update($patientDischargeInstructions);
                    $dischargeMedications->update($patientDischargeMedications);
                    $dischargeFollowUpTreatment->update($patientDischargeFollowUpTreatment);
                    $dischargeFollowUpLaboratories->update($patientDischargeFollowUpLaboratories);
                    $dischargeDoctorsFollowUp->update($patientDischargeDoctorsFollowUp);

                } else {
                    
                    $patient->past_immunization()->create(array_merge($mergeToPatientRelatedTable, $patientPastImmunizationData));
                    $patient->past_medical_history()->create(array_merge($mergeToPatientRelatedTable, $patientPastMedicalHistoryData));
                    $patient->past_medical_procedures()->create(array_merge($mergeToPatientRelatedTable, $pastientPastMedicalProcedureData));
                    $patient->past_bad_habits()->create(array_merge($mergeToPatientRelatedTable, $patientPastBadHabitsData));

                    $patientRegistry = $patient->patientRegistry()->create(array_merge($mergeToRegistryRelatedTable, $patientRegistryData));
                    $patientRegistry->history()->create(array_merge($mergeToRegistryRelatedTable, $patientHistoryData));
                    $patientRegistry->medical_procedures()->create(array_merge($mergeToRegistryRelatedTable, $patientMedicalProcedureData));
                    $patientRegistry->vitals()->create(array_merge($mergeToRegistryRelatedTable, $patientVitalSignsData));
                    $patientRegistry->immunizations()->create(array_merge($mergeToRegistryRelatedTable, $patientImmunizationsData));
                    $patientRegistry->administered_medicines()->create(array_merge($mergeToRegistryRelatedTable, $patientAdministeredMedicineData));

                    $OBGYNHistory = $patientRegistry->oBGYNHistory()->create(array_merge($mergeToRegistryRelatedTable, $patientOBGYNHistory));
                    $OBGYNHistory->PatientPregnancyHistory()->create(array_merge(['OBGYNHistoryID' => $OBGYNHistory->id, 'createdby' => $userId, 'created_at' => $currentTimestamp], $patientPregnancyHistoryData));
                    $OBGYNHistory->gynecologicalConditions()->create(array_merge(['OBGYNHistoryID' => $OBGYNHistory->id, 'createdby' => $userId, 'created_at' => $currentTimestamp], $patientGynecologicalConditions));
                    
                    if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
                        foreach ($request->payload['selectedAllergy'] as $allergy) {

                            $commonData = [
                                'patient_Id'            => $patient_id,
                                'case_No'               => $registry_id,
                                'allergy_Type_Id'       => $allergy['allergy_id'],
                                'createdby'             => $checkUser->idnumber,
                                'created_at'            => Carbon::now(),
                                'isDeleted'             => 0,
                            ];
        
                            $patientAllergyData = array_merge($commonData, [
                                'allergy_description'   => $allergy['allergy_name'] ?? null,
                                'family_History'        => $request->payload['family_History'] ?? null,
                            ]);
                    
                            $patientAllergy = $patientRegistry->allergies()->create($patientAllergyData);
                            $last_inserted_id = $patientAllergy->id;
                        
                            $patientCauseAllergyData = [
                                'assessID'          => $last_inserted_id,
                                'description'       => $allergy['cause'],
                                'duration'          => $request->payload['duration'] ?? null,
                            ];
                        
                            $patientAllergy->cause_of_allergy()->create(array_merge($commonData, $patientCauseAllergyData));
                    
                            if (!empty($allergy['symptoms']) && is_array($allergy['symptoms'])) {
                                $symptomsData = [];
                                foreach ($allergy['symptoms'] as $symptom) {
                                    $symptomsData[] = array_merge($commonData, [
                                        'assessID'              => $last_inserted_id,
                                        'symptom_id'            => $symptom['id'],
                                        'symptom_Description'   => $symptom['description'] ?? null,
                                    ]);
                                }
                                $patientAllergy->symptoms_allergy()->insert($symptomsData);
                            }

                            $patientDrugUsedForAllergyData = [
                                'assessID'          => $last_inserted_id,
                                'drug_Description'  => $request->payload['drug_Description'] ?? null,
                            ];

                            $patient->drug_used_for_allergy()->create(array_merge($commonData, $patientDrugUsedForAllergyData));
                        }
                    }

                    $patientRegistry->bad_habits()->create(array_merge($mergeToRegistryRelatedTable, $patientBadHabitsData));
                    $patientRegistry->patientDoctors()->create(array_merge($mergeToRegistryRelatedTable, $patientDoctorsData));
                    $patientRegistry->physicalAbdomen()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalAbdomenData));
                    $patientRegistry->pertinentSignAndSymptoms()->create(array_merge($mergeToRegistryRelatedTable, $patientPertinentSignAndSymptomsData));
                    $patientRegistry->physicalExamtionChestLungs()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalExamtionChestLungsData));
                    $patientRegistry->courseInTheWard()->create(array_merge($mergeToRegistryRelatedTable, $patientCourseInTheWardData));
                    $patientRegistry->physicalExamtionCVS()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalExamtionCVSData));
                    $patientRegistry->physicalExamtionGeneralSurvey()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalExamtionGeneralSurveyData));
                    $patientRegistry->physicalExamtionHEENT()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalExamtionHEENTData));
                    $patientRegistry->physicalGUIE()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalGUIEData));
                    $patientRegistry->physicalNeuroExam()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalNeuroExamData));
                    $patientRegistry->physicalSkinExtremities()->create(array_merge($mergeToRegistryRelatedTable, $patientPhysicalSkinExtremitiesData));
                    $patientRegistry->medications()->create(array_merge($mergeToRegistryRelatedTable, $patientMedicationsData));

                    $dischargeInstructions = $patientRegistry->dischargeInstructions()->create(array_merge($mergeToRegistryRelatedTable, $patientDischargeInstructions));
                    $dischargeInstructions->dischargeMedications()->create(array_merge(['instruction_Id' => $dischargeInstructions->instruction_Id], $patientDischargeMedications));
                    $dischargeInstructions->dischargeFollowUpTreatment()->create(array_merge(['instruction_Id' => $dischargeInstructions->instruction_Id], $patientDischargeFollowUpTreatment));
                    $dischargeInstructions->dischargeFollowUpLaboratories()->create(array_merge(['instruction_Id' => $dischargeInstructions->instruction_Id], $patientDischargeFollowUpLaboratories));
                    $dischargeInstructions->dischargeDoctorsFollowUp()->create(array_merge(['instruction_Id' => $dischargeInstructions->instruction_Id], $patientDischargeDoctorsFollowUp));
                }

                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_patient_data')->commit();
                DB::connection('sqlsrv')->commit();

                return response()->json([
                    'message' => 'Emergency data updated successfully',
                    'patient' => $patient,
                    'patientRegistry' => $patientRegistry
                ], 200);

        } catch(\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            DB::connection('sqlsrv')->rollBack();

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
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
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


    public function saveAllergiesWithSymptoms(Request $request){
        $selectedAllergies = $request->input('selectedAllergy');

        // Loop through each selected allergy
        foreach ($selectedAllergies as $allergy) {
            $allergyId = $allergy['id']; 

            // Check if there are symptoms to be saved
            if (isset($allergy['symptoms']) && is_array($allergy['symptoms'])) {
                foreach ($allergy['symptoms'] as $symptomDescription) {
                    // Create a new symptom entry for the current allergy
                    Symptom::create([
                        'allergy_id' => $allergyId,
                        'symptom_description' => $symptomDescription, // Save symptom
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Symptoms saved successfully']);
    }

    public function updateAllergy($registry_id) {

        $allergy = PatientAllergies::where('case_No', $registry_id)->first();

        $isUpdated = false;

        if($allergy) {  

            $allergyUpdated           = $allergy->update(['isDeleted' => 1]);
            $causeOfAllergyUpdated    = $allergy->cause_of_allergy()->update(['isDeleted' => 1]);
            $symptomsOfAllergyUpdated = $allergy->symptoms_allergy()->update(['isDeleted' => 1]);
            $drugUseOfAllergyUpdated  = $allergy->drug_used_for_allergy()->update(['isDeleted' => 1]);
    
            if($allergyUpdated && $causeOfAllergyUpdated && $symptomsOfAllergyUpdated && $drugUseOfAllergyUpdated) {
                $isUpdated = true;
            }
        }
    
        return $isUpdated; 
    }
    
    private function preparePatientData($request, $checkUser, $currentTimestamp, $patientId='') {
        return [
                    'patient_Id'                => $request->payload['patient_Id'] ?? $patientId,
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
                    'createdBy'                 => $checkUser->idnumber,
                    'created_at'                => $currentTimestamp,
                    'updatedBy'                 => $checkUser->idnumber,
                    'updated_at'                => $currentTimestamp,   
        ];
    }

    protected function handleMedsysRegistry($existingRegistry, $request, $registrySequence, $registryMopdSequence, $erCaseSequence){

        if (!$existingRegistry) {
            SystemSequence::where('code', 'MERN')->increment('seq_no');
            SystemSequence::where('code', 'MOPD')->increment('seq_no');
            SystemSequence::where('code', 'SERCN')->increment('seq_no');

            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('ERNum');

            $checkMedsysSeriesNo = MedsysSeriesNo::select('OPDId', 'ERNum')->first();
            $registryId = $checkMedsysSeriesNo->OPDId;
            $erCaseNo = $checkMedsysSeriesNo->ERNum;

            $registrySequence->where('code', 'MERN')->update(['recent_generated' => $registryId]);
            $registryMopdSequence->where('code', 'MOPD')->update(['recent_generated' => $registryId]);
            $erCaseSequence->where('code', 'SERCN')->update(['recent_generated' => $erCaseNo]);

        } else {

            $registryId = $request->payload['registry_id'] ?? $registrySequence->seq_no;
            $erCaseNo = $request->payload['er_Case_No'] ?? $erCaseSequence->seq_no;
        }

        return [
            'registryId'    => $registryId,
            'erCaseNo'      => $erCaseNo
        ];

    }

    protected function handleNonMedsysRegistry($existingRegistry, $request, $registrySequence, $erCaseSequence, $opdSequence)
    {
        $registryId = $request->payload['case_No'] ?? intval($registrySequence->seq_no);
        $erCaseNo   = $request->payload['er_Case_No'] ?? intval($erCaseSequence->seq_no);

        if (!$existingRegistry) {

            $registrySequence->where('code', 'MERN')->update(['recent_generated' => $registryId]);
            $opdSequence->where('code', 'MOPD')->update(['recent_generated' => $registryId]);
            $erCaseSequence->where('code', 'SERCN')->update(['recent_generated' => $registryId]);

        }

        return [
            'registryId' => $registryId,
            'erCaseNo' => $erCaseNo
        ];
    }

}