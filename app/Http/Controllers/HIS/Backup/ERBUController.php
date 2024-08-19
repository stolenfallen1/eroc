<?php

namespace App\Http\Controllers\HIS\Backup;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\mscPatientBroughtBy;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
// use APP\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\mscComplaint;
use App\Rules\UniquePatientRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ERBUController extends Controller
{
    //
    public function index() {
        try {
            $data = Patient::query();
            $data->with('sex', 'patientRegistry');
            $data->whereHas('patientRegistry', function($query) {
                $query->where('mscAccount_trans_types', 5); 
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
            // if($data->isEmpty()) {
            //     return response()->json([], 404);
            // }
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

    public function register(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $sequence = SystemSequence::where('code','MPID')->first();
            $registry_sequence = SystemSequence::where('code','MERN')->first();

            $patient_id             = $request->payload['patient_id'] ?? $sequence->seq_no;
            $registry_id            = $request->payload['registry_id'] ?? $registry_sequence->seq_no;
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

            if ($existingPatient) {
                $patient_id = $existingPatient->patient_id;
            } else {
                $patient_id = $sequence->seq_no;
                $sequence->update([
                    'seq_no'            => $sequence->seq_no + 1,
                    'recent_generated'  => $sequence->seq_no,
                ]);
            }

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
            ];

            $patientData = [
                'patient_id'                => $patient_id,
                'title_id'                  => $request->payload['title_id'] ?? null,
                'lastname'                  => ucwords($request->payload['lastname'] ?? null),
                'firstname'                 => ucwords($request->payload['firstname'] ?? null),
                'middlename'                => ucwords($request->payload['middlename'] ?? null),
                'suffix_id'                 => $request->payload['suffix_id'] ?? null,
                'birthdate'                 => $request->payload['birthdate'] ?? null,
                'age'                       => $request->payload['age'] ?? null,
                'sex_id'                    => $request->payload['sex_id'] ?? null,
                'nationality_id'            => $request->payload['nationality_id'] ?? null,
                'religion_id'               => $request->payload['religion_id'] ?? null,
                'civilstatus_id'            => $request->payload['civilstatus_id'] ?? null,
                'typeofbirth_id'            => $request->payload['typeofbirth_id'] ?? null,
                'birthtime'                 => $request->payload['birthtime'] ?? null,
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
                'dialect_spoken'            => $request->payload['dialect_spoken'] ?? null,
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
                'created_at'            => now(),
                'updated_at'            => now()
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
                'updatedby'                                 => Auth()->user()->idnumber,
                'updated_at'                                => now()
            ];

            $patientImmunizationsData = [
                'branch_id'             => 1,
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'vaccine_Id'            => '',
                'administration_Date'   => null,
                'dose'                  => null,
                'site'                  => null,
                'administrator_Name'    => null,
                'Notes'                 => null,
                'createdby'             => Auth()->user()->idnumber,
                'updatedby'             => Auth()->user()->idnumber,
                'created_at'            => now(),
                'updated_at'            => now()
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
                'created_at'                    => now(),
                'updated_at'                    => now()
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
                'created_at'                => now(),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => now()
            ];

            $patientRegistryData = [
                'branch_Id'                     =>  1,
                'patient_Id'                    => $patient_id,
                'case_No'                       => $registry_id,
                'register_Source'               => $request->payload['register_source'] ?? null,
                'mscAccount_Type'               => $request->payload['mscAccount_type'] ?? '',
                'mscAccount_Discount_Id'        => $request->payload['mscAccount_discount_id'] ?? null,
                'mscAccount_Trans_Types'        => $request->payload['mscAccount_trans_types'] ?? 5, 
                'mscPatient_Category'           => $request->payload['mscPatient_category'] ?? null,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? null,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? 100,
                'mscService_Type'               => $request->payload['mscService_type'] ?? null,
                'mscService_Type2'              => $request->payload['mscService_type2'] ?? null,
                'queue_Number'                  => $request->payload['queue_number'] ?? null,
                'arrived_Date'                  => $request->payload['arrived_date'] ?? null,
                'registry_Userid'               => Auth()->user()->idnumber,
                'registry_Date'                 => Carbon::now(),
                'registry_Status'               => $request->payload['registry_status'] ?? null,
                'discharged_Userid'             => $request->payload['discharged_userid'] ?? null,
                'discharged_Date'               => $request->payload['discharged_date'] ?? null,
                'billed_Userid'                 => $request->payload['billed_userid'] ?? null,
                'billed_Date'                   => $request->payload['billed_date'] ?? null,
                'billed_Remarks'                => $request->payload['billed_remarks'] ?? null,
                'mgh_Userid'                    => $request->payload['mgh_userid'] ?? null,
                'mgh_Datetime'                  => $request->payload['mgh_datetime'] ?? null,
                'untag_Mgh_Userid'              => $request->payload['untag_mgh_userid'] ?? null,
                'untag_Mgh_Datetime'            => $request->payload['untag_mgh_datetime'] ?? null,
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? false,
                'hold_Userid'                   => $request->payload['hold_userid'] ?? null,
                'hold_No'                       => $request->payload['hold_no'] ?? null,
                'hold_Date'                     => $request->payload['hold_date'] ?? null,
                'hold_Remarks'                  => $request->payload['hold_remarks'] ?? null,
                'isRevoked'                     => $request->payload['isRevoked'] ?? false,
                'revokedBy'                     => $request->payload['revokedBy'] ?? null,
                'revoked_Date'                  => $request->payload['revoked_date'] ?? null,
                'revoked_Remarks'               => $request->payload['revoked_remarks'] ?? null,
                'guarantor_Id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? null,
                'guarantor_Name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? null,
                'guarantor_Approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? null,
                'guarantor_Approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? null,
                'guarantor_Approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? null,
                'guarantor_Validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? null,
                'guarantor_Approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? null,
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
                'guarantor_Credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? null,
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? false,
                'philhealth_Number'             => $request->payload['philhealth_number'] ?? null,
                'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? false,
                'medical_Package_Id'            => $request->payload['Medical_Package_id'] ?? null,
                'medical_Package_Name'          => $request->payload['Medical_Package_name'] ?? null,
                'medical_Package_Amount'        => $request->payload['Medical_Package_amount'] ?? null,
                'chief_Complaint_Description'   => $request->payload['clinical_chief_complaint'] ?? null,
                'impression'                    => $request->payload['impression'] ?? null,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? false,
                'illness_Type'                  => $request->payload['illness_type'] ?? null,
                'isreferredFrom'                => $request->payload['isreferredfrom'] ?? false,
                'referred_From_HCI'             => $request->payload['referred_from_HCI'] ?? null,
                'referred_From_HCI_address'     => $request->payload['referred_from_HCI_address'] ?? null,
                'referred_From_HCI_code'        => $request->payload['referred_from_HCI_code'] ?? null,
                'referred_To_HCI'               => $request->payload['referring_doctor'] ?? null,
                'referring_Doctor'              => $request->payload['referring_Doctor'] ?? null,
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
                'attending_Doctor'              => $request->payload['selectedConsultant'][0]['doctor_code'] ?? null,
                'attending_Doctor_fullname'     => $request->payload['selectedConsultant'][0]['doctor_name'] ?? null,
                'mscDisposition_Id'             => $request->payload['mscDispositions'] ?? null,
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
                'mscCase_Result_Id'             => $request->payload['mscCase_result_id'] ?? null,
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? false,
                'barcode_Image'                 => $request->payload['barcode_image'] ?? null,
                'barcode_Code_Id'               => $request->payload['barcode_code_id'] ?? null,
                'barcode_Code_String'           => $request->payload['barcode_code_string'] ?? null,
                'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? false,
                'registry_Remarks'              => $request->payload['registry_remarks'] ?? null,
                'er_Bedno'                      => $request->payload['area_bed_no'] ?? null,
                'CreatedBy'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'UpdatedBy'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ];

        
            $today = Carbon::now()->format('Y-m-d');
            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();
   
            $patient = Patient::updateOrCreate($patientRule, $patientData);
            PatientHistory::create($patientHistoryData);
            PatientMedicalProcedures::create($patientMedicalProcedureData);
            PatientVitalSigns::create($patientVitalSignsData);
            if(!$existingRegistry) {
                $patientRegistry = $patient->patientRegistry()->updateOrCreate($patientRegistryData);
                PatientImmunizations::create($patientImmunizationsData);
                PatientAdministeredMedicines::create($patientAdministeredMedicineData);
            } else {
                throw new \Exception('Patient already registered today');
            }

            if(!$patient || !$patientRegistry) {
                throw new \Exception('Error');
            }

            if(!isset($request->payload['patient_id'])) {
                $sequence->update([
                    'seq_no' => $sequence->seq_no + 1,
                    'recent_generated' => $sequence->seq_no,
                ]);
            }
            if(!isset($request->payload['registry_id'])) {
                $registry_sequence->update([
                    'seq_no' => $registry_sequence->seq_no + 1,
                    'recent_generated' => $registry_sequence->seq_no,
                ]);
            } 

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
        DB::beginTransaction();
        try {
            $patient = Patient::findOrFail($id);

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

            $patient->update([
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
                'branch_id'                 => $request->payload['branch_id'] ?? $patient->branch_id,
                'GSIS_Number'               => $request->payload['GSIS_Number'] ?? $patient->GSIS_Number,
                'SSS_Number'                => $request->payload['SSS_Number'] ?? $patient->SSS_Number,
                'is_old_patient'            => $request->payload['is_old_patient'] ?? $patient->is_old_patient,
                'portal_access_uid'         => $request->payload['portal_access_uid'] ?? $patient->portal_access_uid,
                'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? $patient->portal_access_pwd,
                'isBlacklisted'             => $request->payload['isBlacklisted'] ?? $patient->isBlacklisted,
                'blacklist_reason'          => $request->payload['blacklist_reason'] ?? $patient->blacklist_reason,
                'isAbscond'                 => $request->payload['isAbscond'] ?? $patient->isAbscond,
                'abscond_details'           => $request->payload['abscond_details'] ?? $patient->abscond_details,
                'updatedBy'                 => Auth()->user()->idnumber,
                'updated_at'                => Carbon::now(),
            ]);
            
            $patient_id         = $patient->patient_id;
            $patientRegistry    = PatientRegistry::where('patient_id', $patient_id)->first();

            $patientRegistry->update([
                'mscBranches_id'                => $request->payload['mscBranches_id'] ?? $patientRegistry->mscBranches_id,
                'mscAccount_type'               => $request->payload['mscAccount_type'] ?? $patientRegistry->mscAccount_type,
                'mscAccount_discount_id'        => $request->payload['mscAccount_discount_id'] ?? $patientRegistry->mscAccount_discount_id,
                'mscAccount_trans_types'        => $request->payload['mscAccount_trans_types'] ?? $patientRegistry->mscAccount_trans_types,  
                'mscPatient_category'           => $request->payload['mscPatient_category'] ?? $patientRegistry->mscPatient_category,
                'mscPrice_Groups'               => $request->payload['mscPrice_Groups'] ?? $patientRegistry->mscPrice_Groups,
                'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'] ?? $patientRegistry->mscPrice_Schemes,
                'mscService_type'               => $request->payload['mscService_type'] ?? $patientRegistry->mscService_type,
                'mscService_type2'              => $request->payload['mscService_type2'] ?? $patientRegistry->mscService_type2,
                'mscpatient_type'               => $request->payload['mscpatient_type'] ?? $patientRegistry->mscpatient_type,
                'queue_number'                  => $request->payload['queue_number'] ?? $patientRegistry->queue_number,
                'arrived_date'                  => $request->payload['arrived_date'] ?? $patientRegistry->arrived_date,
                'registry_userid'               => Auth()->user()->idnumber,
                'registry_date'                 => Carbon::now(),
                'registry_status'               => $request->payload['registry_status'] ?? $patientRegistry->registry_status,
                'registry_department_id'        => $request->payload['registry_department_id'] ?? $patientRegistry->registry_department_id,
                'discharged_userid'             => $request->payload['discharged_userid'] ?? $patientRegistry->discharged_userid,
                'discharged_date'               => $request->payload['discharged_date'] ?? $patientRegistry->discharged_date,
                'billed_userid'                 => $request->payload['billed_userid'] ?? $patientRegistry->billed_userid,
                'billed_date'                   => $request->payload['billed_date'] ?? $patientRegistry->billed_date,
                'billed_remarks'                => $request->payload['billed_remarks'] ?? $patientRegistry->billed_remarks,
                'mgh_userid'                    => $request->payload['mgh_userid'] ?? $patientRegistry->mgh_userid,
                'mgh_datetime'                  => $request->payload['mgh_datetime'] ?? $patientRegistry->mgh_datetime,
                'untag_mgh_userid'              => $request->payload['untag_mgh_userid'] ?? $patientRegistry->untag_mgh_userid,
                'untag_mgh_datetime'            => $request->payload['untag_mgh_datetime'] ?? $patientRegistry->untag_mgh_datetime,
                'isHoldReg'                     => $request->payload['isHoldReg'] ?? $patientRegistry->isHoldReg,
                'hold_userid'                   => $request->payload['hold_userid'] ?? $patientRegistry->hold_userid,
                'hold_no'                       => $request->payload['hold_no'] ?? $patientRegistry->hold_no,
                'hold_date'                     => $request->payload['hold_date'] ?? $patientRegistry->hold_date,
                'hold_remarks'                  => $request->payload['hold_remarks'] ?? $patientRegistry->hold_remarks,
                'isRevoked'                     => $request->payload['isRevoked'] ?? $patientRegistry->isRevoked,
                'revokedBy'                     => $request->payload['revokedBy'] ?? $patientRegistry->revokedBy,
                'revoked_date'                  => $request->payload['revoked_date'] ?? $patientRegistry->revoked_date,
                'revoked_remarks'               => $request->payload['revoked_remarks'] ?? $patientRegistry->revoked_remarks,
                'guarantor_id'                  => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patientRegistry->guarantor_id,
                'guarantor_name'                => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? $patientRegistry->guarantor_name,
                'guarantor_approval_code'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? $patientRegistry->guarantor_approval_code,
                'guarantor_approval_no'         => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? $patientRegistry->guarantor_approval_no,
                'guarantor_approval_date'       => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? $patientRegistry->guarantor_approval_date,
                'guarantor_validity_date'       => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? $patientRegistry->guarantor_validity_date,
                'guarantor_approval_remarks'    => $request->payload['guarantor_approval_remarks'] ?? $patientRegistry->guarantor_approval_remarks,
                'isWithCreditLimit'             => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false) ?? $patientRegistry->isWithCreditLimit,
                'guarantor_credit_Limit'        => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? $patientRegistry->guarantor_credit_Limit,
                'isWithPhilHealth'              => $request->payload['isWithPhilHealth'] ?? $patientRegistry->isWithPhilHealth,
                'msc_PHIC_Memberships'          => $request->payload['msc_PHIC_Memberships'] ?? $patientRegistry->msc_PHIC_Memberships,
                'philhealth_number'             => $request->payload['philhealth_number'] ?? $patientRegistry->philhealth_number,
                'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? $patientRegistry->isWithMedicalPackage,
                'Medical_Package_id'            => $request->payload['Medical_Package_id'] ?? $patientRegistry->Medical_Package_id,
                'Medical_Package_name'          => $request->payload['Medical_Package_name'] ?? $patientRegistry->Medical_Package_name,
                'Medical_Package_amount'        => $request->payload['Medical_Package_amount'] ?? $patientRegistry->Medical_Package_amount,
                'clinical_chief_complaint'      => $request->payload['clinical_chief_complaint'] ?? $patientRegistry->clinical_chief_complaint,
                'impression'                    => $request->payload['impression'] ?? $patientRegistry->impression,
                'isCriticallyIll'               => $request->payload['isCriticallyIll'] ?? $patientRegistry->isCriticallyIll,
                'illness_type'                  => $request->payload['illness_type'] ?? $patientRegistry->illness_type,
                'isreferredfrom'                => $request->payload['isreferredfrom'] ?? $patientRegistry->isreferredfrom,
                'referred_from_HCI'             => $request->payload['referred_from_HCI'] ?? $patientRegistry->referred_from_HCI,
                'referred_from_HCI_address'     => $request->payload['referred_from_HCI_address'] ?? $patientRegistry->referred_from_HCI_address,
                'referred_from_HCI_code'        => $request->payload['referred_from_HCI_code'] ?? $patientRegistry->referred_from_HCI_code,
                'referring_doctor'              => $request->payload['referring_doctor'] ?? $patientRegistry->referring_doctor,
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
                'attending_doctor'              => $request->payload['selectedConsultant'][0]['doctor_code'] ?? $patientRegistry->attending_doctor,
                'attending_doctor_fullname'     => $request->payload['selectedConsultant'][0]['doctor_name'] ?? $patientRegistry->attending_doctor_fullname,
                'mscDispositions'               => $request->payload['mscDispositions'] ?? $patientRegistry->mscDispositions,
                'mscAdmResults'                 => $request->payload['mscAdmResults'] ?? $patientRegistry->mscAdmResults,
                'mscDeath_Types'                => $request->payload['mscDeath_Types'] ?? $patientRegistry->mscDeath_Types,
                'bmi'                           => $request->payload['bmi'] ?? $patientRegistry->bmi,
                'weight'                        => $request->payload['weight'] ?? $patientRegistry->weight,
                'weightUnit'                    => $request->payload['weightUnit'] ?? $patientRegistry->weightUnit,
                'height'                        => $request->payload['height'] ?? $patientRegistry->height,
                'height_Unit'                   => $request->payload['height_Unit'] ?? $patientRegistry->height_Unit,
                'voucher_no'                    => $request->payload['voucher_no'] ?? $patientRegistry->voucher_no,
                'appt_Trans'                    => $request->payload['appt_Trans'] ?? $patientRegistry->appt_Trans,
                'LateCharges'                   => $request->payload['LateCharges'] ?? $patientRegistry->LateCharges,
                'mscdisposition_id'             => $request->payload['mscdisposition_id'] ?? $patientRegistry->mscdisposition_id,
                'mscCase_result_id'             => $request->payload['mscCase_result_id'] ?? $patientRegistry->mscCase_result_id,
                'mscDeath_types_id'             => $request->payload['mscDeath_types_id'] ?? $patientRegistry->mscDeath_types_id,
                'death_date'                    => $request->payload['death_date'] ?? $patientRegistry->death_date,
                'mscDelivery_types_id'          => $request->payload['mscDelivery_types_id'] ?? $patientRegistry->mscDelivery_types_id,
                'isdied_less48Hours'            => $request->payload['isdied_less48Hours'] ?? $patientRegistry->isdied_less48Hours,
                'isAutopsy'                     => $request->payload['isAutopsy'] ?? $patientRegistry->isAutopsy,
                'barcode_image'                 => $request->payload['barcode_image'] ?? $patientRegistry->barcode_image,
                'barcode_code_id'               => $request->payload['barcode_code_id'] ?? $patientRegistry->barcode_code_id,
                'barcode_code_string'           => $request->payload['barcode_code_string'] ?? $patientRegistry->barcode_code_string,
                'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? $patientRegistry->isWithConsent_DPA,
                'registry_remarks'              => $request->payload['registry_remarks'] ?? $patientRegistry->registry_remarks, 
                'UpdatedBy'                     => Auth()->user()->idnumber,
                'updated_at'                        => Carbon::now(),
            ]);   
            
            DB::commit();
            return response()->json([
                'message' => 'Outpatient data updated successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'   => 'Failed to update outpatient data',
                'error'     => $e->getMessage()
            ], 500);
        }
    }

    public function receiver(Request $request) {

        $sequence = SystemSequence::where('code','MPID')->first();
        $registry_sequence = SystemSequence::where('code','MERN')->first();

        $patient_id             = $request->payload['patient_id'] ?? $sequence->seq_no;
        $registry_id            = $request->payload['registry_id'] ?? $registry_sequence->seq_no;

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

        $data = [
            'patientRule' => [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
            ],

            'patientData' => [
                'patient_id'                => $patient_id,
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
                'created_at'            => now(),
                'updated_at'            => now()
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
                'updatedby'                                 => Auth()->user()->idnumber,
                'updated_at'                                => now()
            ],

            'patientImmunizationsData' => [
                'branch_id'             => 1,
                'patient_Id'            => $patient_id,
                'case_No'               => $registry_id,
                'vaccine_Id'            => '',
                'administration_Date'   => null,
                'dose'                  => null,
                'site'                  => null,
                'administrator_Name'    => null,
                'Notes'                 => null,
                'createdby'             => Auth()->user()->idnumber,
                'updatedby'             => Auth()->user()->idnumber,
                'created_at'            => now(),
                'updated_at'            => now()
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
                'created_at'                    => now(),
                'updated_at'                    => now()
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
                'created_at'                => now(),
                'updatedby'                 => Auth()->user()->idnumber,
                'updated_at'                => now()
            ],

            'patientRegistryData' => [
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
                'chief_complaint_description'   => $request->payload['clinical_chief_complaint'] ?? null,
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
                'UpdatedBy'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ]
        ];

        return $data;
    }
}
