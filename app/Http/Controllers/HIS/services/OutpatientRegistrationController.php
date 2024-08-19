<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
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
                $query->where('mscAccount_trans_types', 2); 
                $query->where('isRevoked', 0);
                $query->whereDate('registry_date', $today)
                    ->where(function($q) use ($today) {
                        $q->whereNull('discharged_date')
                            ->orWhereDate('discharged_date', '>=', $today);
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

            $bloodPressureSystolic  = $request->payload['bloodPressureSystolic'] ?? null;
            $bloodPressureDiastolic = $request->payload['bloodPressureDiastolic'] ?? null;

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

            $patient = Patient::updateOrCreate(
                [
                    'lastname'                  => $request->payload['lastname'],
                    'firstname'                 => $request->payload['firstname'], 
                    'birthdate'                 => $request->payload['birthdate'],
                ],
                [
                    'patient_id'                => $patient_id,
                    'title_id'                  => $request->payload['title_id'] ?? null,
                    'lastname'                  => ucwords($request->payload['lastname']),
                    'firstname'                 => ucwords($request->payload['firstname']),
                    'middlename'                => ucwords($request->payload['middlename'] ?? null),
                    'suffix_id'                 => $request->payload['suffix_id'] ?? null,
                    'birthdate'                 => $request->payload['birthdate'],
                    'age'                       => $request->payload['age'] ?? null,
                    'birthplace'                => ucwords($request->payload['birthplace'] ?? null),
                    'sex_id'                    => $request->payload['sex_id'],
                    'nationality_id'            => $request->payload['nationality_id'] ?? null,
                    'religion_id'               => $request->payload['religion_id'] ?? null,
                    'civilstatus_id'            => $request->payload['civilstatus_id'],
                    'typeofbirth_id'            => $request->payload['typeofbirth_id'] ?? null,
                    'birthtime'                 => $request->payload['birthtime'] ?? null,
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
                    'isSeniorCitizen'           => $request->payload['age'] >= 60 ? true : false,
                    'SeniorCitizen_ID_Number'   => $request->payload['SeniorCitizen_ID_Number'] ?? null,
                    'isPWD'                     => $request->payload['isPWD'] ?? false,
                    'PWD_ID_Number'             => $request->payload['PWD_ID_Number'] ?? null,
                    'isPhilhealth_Member'       => $request->payload['isPhilhealth_Member'] ?? false,
                    'Philhealth_Number'         => $request->payload['Philhealth_Number'] ?? null,
                    'isEmployee'                => $request->payload['isEmployee'] ?? false,
                    'branch_id'                 => $request->payload['branch_id'] ?? 1,
                    'GSIS_Number'               => $request->payload['GSIS_Number'] ?? null,
                    'SSS_Number'                => $request->payload['SSS_Number'] ?? null,
                    'is_old_patient'            => $request->payload['is_old_patient'] ?? false,
                    'portal_access_uid'         => $request->payload['portal_access_uid'] ?? null,
                    'portal_access_pwd'         => $request->payload['portal_access_pwd'] ?? null,
                    'isBlacklisted'             => $request->payload['isBlacklisted'] ?? false,
                    'blacklist_reason'          => $request->payload['blacklist_reason'] ?? null,
                    'isAbscond'                 => $request->payload['isAbscond'] ?? false,
                    'abscond_details'           => $request->payload['abscond_details'] ?? null,
                    'createdBy'                 => Auth()->user()->idnumber,
                    'updatedBy'                 => Auth()->user()->idnumber,
                    'created_at'                => Carbon::now(),
                    'updated_at'                => Carbon::now(),
                ]
            );

            PatientVitalSigns::create([
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
            ]);
            PatientMedicalProcedures::create([
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
            ]);
            PatientMedicalProcedures::create([
                'patient_Id'                    => $patient_id,
                // 'case_No'                       => $registry_id,
                'case_No'                       => '123',
                'description'                   => '',
                'date_Of_Procedure'             => '',
                'performing_Doctor_id'          => '',
                'performing_Doctor_Fullname'    => '',
                'createdby'                     => Auth()->user()->idnumber,
                'created_at'                    => Carbon::now(),
                'updatedby'                     => Auth()->user()->idnumber,
                'updated_at'                    => Carbon::now(),
            ]);
            PatientHistory::create([
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
            ]);

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingRegistry) {
                $patientRegistry = PatientRegistry::updateOrCreate([
                    'patient_Id'                    => $patient_id,
                    'case_No'                       => $registry_id,
                    'branch_id'                     => $request->payload['mscBranches_id'] ?? 1,
                    'register_source'               => $request->payload['register_source'] ?? null,
                    'register_Casetype'             => $request->payload['register_Casetype'] ?? null, 
                    'patient_age'                   => $request->payload['age'] ?? null,
                    'mscAccount_type'               => $request->payload['mscAccount_type'] ?? 1,
                    'mscAccount_discount_id'        => $request->payload['mscAccount_discount_id'] ?? null,
                    'mscAccount_trans_types'        => $request->payload['mscAccount_trans_types'], 
                    'mscPatient_category'           => $patient_category,
                    'mscPrice_Groups'               => $request->payload['mscPrice_Groups'],
                    'mscPrice_Schemes'              => $request->payload['mscPrice_Schemes'],
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
                    'mscPHIC_Membership_Type_id'    => $request->payload['msc_PHIC_Memberships'] ?? null,
                    'philhealth_number'             => $request->payload['philhealth_number'] ?? null,
                    'isWithMedicalPackage'          => $request->payload['isWithMedicalPackage'] ?? false,
                    'Medical_Package_id'            => $request->payload['Medical_Package_id'] ?? null,
                    'Medical_Package_name'          => $request->payload['Medical_Package_name'] ?? null,
                    'Medical_Package_amount'        => $request->payload['Medical_Package_amount'] ?? null,
                    'chief_complaint_description'   => $request->payload['chief_complaint_description'] ?? null,
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
                    'attending_doctor'              => $request->payload['selectedConsultant'][0]['attending_doctor'] ?? null,
                    'attending_doctor_fullname'     => $request->payload['selectedConsultant'][0]['attending_doctor_fullname'] ?? null,
                    'bmi'                           => $request->payload['bmi'] ?? null,
                    'weight'                        => $request->payload['weight'] ?? null,
                    'weightUnit'                    => $request->payload['weightUnit'] ?? null,
                    'height'                        => $request->payload['height'] ?? null,
                    'heightUnit'                    => $request->payload['height_Unit'] ?? null,
                    'bloodPressureSystolic'         => $bloodPressureSystolic,
                    'bloodPressureDiastolic'        => $bloodPressureDiastolic,
                    'isOpenLateCharges'             => $request->payload['LateCharges'] ?? null,
                    'mscdisposition_id'             => $request->payload['mscdisposition_id'] ?? null,
                    'mscCase_result_id'             => $request->payload['mscCase_result_id'] ?? null,
                    'dateofdeath'                   => $request->payload['death_date'] ?? null,
                    'isdiedless48Hours'             => $request->payload['isdied_less48Hours'] ?? false,
                    'isAutopsy'                     => $request->payload['isAutopsy'] ?? false,
                    'barcode_image'                 => $request->payload['barcode_image'] ?? null,
                    'barcode_code_id'               => $request->payload['barcode_code_id'] ?? null,
                    'barcode_code_string'           => $request->payload['barcode_code_string'] ?? null,
                    'isWithConsent_DPA'             => $request->payload['isWithConsent_DPA'] ?? false,
                    'registry_remarks'              => $request->payload['registry_remarks'] ?? null, 
                    'CreatedBy'                     => Auth()->user()->idnumber,
                    'created_at'                    => Carbon::now(),
                ]);

            } else {
                throw new \Exception('Patient already registered today');
            }

            if (!$patient || !$patientRegistry) {
                throw new \Exception('Registration failed, rollback transaction');
            } else {
                PatientImmunizations::create([
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
                ]);
                PatientAdministeredMedicines::create([
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
                ]);

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

            $patient->update([
                'title_id' => $request->payload['title_id'] ?? $patient->title_id,
                'lastname' => ucwords($request->payload['lastname']) ?? $patient->lastname,
                'firstname' => ucwords($request->payload['firstname']) ?? $patient->firstname,
                'middlename' => ucwords($request->payload['middlename']) ?? $patient->middlename,
                'suffix_id' => $request->payload['suffix_id'] ?? $patient->suffix_id,
                'birthdate' => $request->payload['birthdate'] ?? $patient->birthdate,
                'age' => $request->payload['age'] ?? $patient->age,
                'sex_id' => $request->payload['sex_id'] ?? $patient->sex_id,
                'nationality_id' => $request->payload['nationality_id'] ?? $patient->nationality_id,
                'religion_id' => $request->payload['religion_id'] ?? $patient->religion_id,
                'civilstatus_id' => $request->payload['civilstatus_id'] ?? $patient->civilstatus_id,
                'typeofbirth_id' => $request->payload['typeofbirth_id'] ?? $patient->typeofbirth_id,
                'birthtime' => $request->payload['birthtime'] ?? $patient->birthtime,
                'typeofdeath_id' => $request->payload['typeofdeath_id'] ?? $patient->typeofdeath_id,
                'timeofdeath' => $request->payload['timeofdeath'] ?? $patient->timeofdeath,
                'bloodtype_id' => $request->payload['bloodtype_id'] ?? $patient->bloodtype_id,
                'bldgstreet' => $request->payload['address']['bldgstreet'] ?? $patient->bldgstreet,
                'region_id' => $request->payload['address']['region_id'] ?? $patient->region_id,
                'province_id' => $request->payload['address']['province_id'] ?? $patient->province_id,
                'municipality_id' => $request->payload['address']['municipality_id'] ?? $patient->municipality_id,
                'barangay_id' => $request->payload['address']['barangay_id'] ?? $patient->barangay_id,
                'zipcode_id' => $request->payload['address']['zipcode_id'] ?? $patient->zipcode_id,
                'country_id' => $request->payload['address']['country_id'] ?? $patient->country_id,
                'occupation' => $request->payload['occupation'] ?? $patient->occupation,
                'telephone_number' => $request->payload['telephone_number'] ?? $patient->telephone_number,
                'mobile_number' => $request->payload['mobile_number'] ?? $patient->mobile_number,
                'email_address' => $request->payload['email_address'] ?? $patient->email_address,
                'isSeniorCitizen' => $request->payload['isSeniorCitizen'] ?? $patient->isSeniorCitizen,
                'SeniorCitizen_ID_Number' => $request->payload['SeniorCitizen_ID_Number'] ?? $patient->SeniorCitizen_ID_Number,
                'isPWD' => $request->payload['isPWD'] ?? $patient->isPWD,
                'PWD_ID_Number' => $request->payload['PWD_ID_Number'] ?? $patient->PWD_ID_Number,
                'isPhilhealth_Member' => $request->payload['isPhilhealth_Member'] ?? $patient->isPhilhealth_Member,
                'Philhealth_Number' => $request->payload['Philhealth_Number'] ?? $patient->Philhealth_Number,
                'isEmployee' => $request->payload['isEmployee'] ?? $patient->isEmployee,
                'branch_id' => $request->payload['branch_id'] ?? $patient->branch_id,
                'GSIS_Number' => $request->payload['GSIS_Number'] ?? $patient->GSIS_Number,
                'SSS_Number' => $request->payload['SSS_Number'] ?? $patient->SSS_Number,
                'is_old_patient' => $request->payload['is_old_patient'] ?? $patient->is_old_patient,
                'portal_access_uid' => $request->payload['portal_access_uid'] ?? $patient->portal_access_uid,
                'portal_access_pwd' => $request->payload['portal_access_pwd'] ?? $patient->portal_access_pwd,
                'isBlacklisted' => $request->payload['isBlacklisted'] ?? $patient->isBlacklisted,
                'blacklist_reason' => $request->payload['blacklist_reason'] ?? $patient->blacklist_reason,
                'isAbscond' => $request->payload['isAbscond'] ?? $patient->isAbscond,
                'abscond_details' => $request->payload['abscond_details'] ?? $patient->abscond_details,
                'updatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);
            
            $patient_id = $patient->patient_id;
            $patientRegistry = PatientRegistry::where('patient_id', $patient_id)->first(); 
            $patientRegistry->update([
                'branch_id' => $request->payload['mscBranches_id'] ?? $patientRegistry->mscBranches_id,
                'mscAccount_type' => $request->payload['mscAccount_type'] ?? $patientRegistry->mscAccount_type,
                'mscAccount_discount_id' => $request->payload['mscAccount_discount_id'] ?? $patientRegistry->mscAccount_discount_id,
                'mscAccount_trans_types' => $request->payload['mscAccount_trans_types'] ?? $patientRegistry->mscAccount_trans_types,  
                'mscPrice_Groups' => $request->payload['mscPrice_Groups'] ?? $patientRegistry->mscPrice_Groups,
                'mscPrice_Schemes' => $request->payload['mscPrice_Schemes'] ?? $patientRegistry->mscPrice_Schemes,
                'mscService_type' => $request->payload['mscService_type'] ?? $patientRegistry->mscService_type,
                'mscService_type2' => $request->payload['mscService_type2'] ?? $patientRegistry->mscService_type2,
                'queue_number' => $request->payload['queue_number'] ?? $patientRegistry->queue_number,
                'arrived_date' => $request->payload['arrived_date'] ?? $patientRegistry->arrived_date,
                'registry_userid' => Auth()->user()->idnumber,
                'registry_date' => Carbon::now(),
                'registry_status' => $request->payload['registry_status'] ?? $patientRegistry->registry_status,
                'registry_hostname' => (new GetIP())->getHostname() ?? null,
                'discharged_userid' => $request->payload['discharged_userid'] ?? $patientRegistry->discharged_userid,
                'discharged_date' => $request->payload['discharged_date'] ?? $patientRegistry->discharged_date,
                'discharged_hostname' => (new GetIP())->getHostname() ?? null,
                'billed_userid' => $request->payload['billed_userid'] ?? $patientRegistry->billed_userid,
                'billed_date' => $request->payload['billed_date'] ?? $patientRegistry->billed_date,
                'billed_remarks' => $request->payload['billed_remarks'] ?? $patientRegistry->billed_remarks,
                'billed_hostname' => (new GetIP())->getHostname() ?? null,
                'mgh_userid' => $request->payload['mgh_userid'] ?? $patientRegistry->mgh_userid,
                'mgh_datetime' => $request->payload['mgh_datetime'] ?? $patientRegistry->mgh_datetime,
                'mgh_hostname' => (new GetIP())->getHostname() ?? null,
                'untag_mgh_userid' => $request->payload['untag_mgh_userid'] ?? $patientRegistry->untag_mgh_userid,
                'untag_mgh_datetime' => $request->payload['untag_mgh_datetime'] ?? $patientRegistry->untag_mgh_datetime,
                'untag_mgh_hostname' => (new GetIP())->getHostname() ?? null,
                'isHoldReg' => $request->payload['isHoldReg'] ?? $patientRegistry->isHoldReg,
                'hold_userid' => $request->payload['hold_userid'] ?? $patientRegistry->hold_userid,
                'hold_no' => $request->payload['hold_no'] ?? $patientRegistry->hold_no,
                'hold_date' => $request->payload['hold_date'] ?? $patientRegistry->hold_date,
                'hold_remarks' => $request->payload['hold_remarks'] ?? $patientRegistry->hold_remarks,
                'hold_hostname' => (new GetIP())->getHostname() ?? null,
                'isRevoked' => $request->payload['isRevoked'] ?? $patientRegistry->isRevoked,
                'revokedBy' => $request->payload['revokedBy'] ?? $patientRegistry->revokedBy,
                'revoked_date' => $request->payload['revoked_date'] ?? $patientRegistry->revoked_date,
                'revoked_remarks' => $request->payload['revoked_remarks'] ?? $patientRegistry->revoked_remarks,
                'guarantor_id' => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? $patientRegistry->guarantor_id,
                'guarantor_name' => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? $patientRegistry->guarantor_name,
                'guarantor_approval_code' => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? $patientRegistry->guarantor_approval_code,
                'guarantor_approval_no' => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? $patientRegistry->guarantor_approval_no,
                'guarantor_approval_date' => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? $patientRegistry->guarantor_approval_date,
                'guarantor_validity_date' => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? $patientRegistry->guarantor_validity_date,
                'guarantor_approval_remarks' => $request->payload['guarantor_approval_remarks'] ?? $patientRegistry->guarantor_approval_remarks,
                'isWithCreditLimit' => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false) ?? $patientRegistry->isWithCreditLimit,
                'guarantor_credit_Limit' => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? $patientRegistry->guarantor_credit_Limit,
                'isWithPhilHealth' => $request->payload['isWithPhilHealth'] ?? $patientRegistry->isWithPhilHealth,
                'mscPHIC_Membership_Type_id' => $request->payload['msc_PHIC_Memberships'] ?? $patientRegistry->msc_PHIC_Memberships,
                'philhealth_number' => $request->payload['philhealth_number'] ?? $patientRegistry->philhealth_number,
                'isWithMedicalPackage' => $request->payload['isWithMedicalPackage'] ?? $patientRegistry->isWithMedicalPackage,
                'Medical_Package_id' => $request->payload['Medical_Package_id'] ?? $patientRegistry->Medical_Package_id,
                'Medical_Package_name' => $request->payload['Medical_Package_name'] ?? $patientRegistry->Medical_Package_name,
                'Medical_Package_amount' => $request->payload['Medical_Package_amount'] ?? $patientRegistry->Medical_Package_amount,
                'chief_complaint_description' => $request->payload['chief_complaint_description'] ?? $patientRegistry->chief_complaint_description,
                'impression' => $request->payload['impression'] ?? $patientRegistry->impression,
                'isCriticallyIll' => $request->payload['isCriticallyIll'] ?? $patientRegistry->isCriticallyIll,
                'illness_type' => $request->payload['illness_type'] ?? $patientRegistry->illness_type,
                'isreferredfrom' => $request->payload['isreferredfrom'] ?? $patientRegistry->isreferredfrom,
                'referred_from_HCI' => $request->payload['referred_from_HCI'] ?? $patientRegistry->referred_from_HCI,
                'referred_from_HCI_address' => $request->payload['referred_from_HCI_address'] ?? $patientRegistry->referred_from_HCI_address,
                'referred_from_HCI_code' => $request->payload['referred_from_HCI_code'] ?? $patientRegistry->referred_from_HCI_code,
                'referring_doctor' => $request->payload['referring_doctor'] ?? $patientRegistry->referring_doctor,
                'isHemodialysis' => $isHemodialysis ?? $patientRegistry->isHemodialysis,
                'isPeritoneal' => $isPeritoneal ?? $patientRegistry->isPeritoneal,
                'isLINAC' => $isLINAC ?? $patientRegistry->isLINAC,
                'isCOBALT' => $isCOBALT ?? $patientRegistry->isCOBALT,
                'isBloodTrans' => $isBloodTrans ?? $patientRegistry->isBloodTrans,
                'isChemotherapy' => $isChemotherapy ?? $patientRegistry->isChemotherapy,
                'isBrachytherapy' => $isBrachytherapy ?? $patientRegistry->isBrachytherapy,
                'isDebridement' => $isDebridement ?? $patientRegistry->isDebridement,
                'isTBDots' => $isTBDots ?? $patientRegistry->isTBDots,
                'isPAD' => $isPAD ?? $patientRegistry->isPAD,
                'isRadioTherapy' => $isRadioTherapy ?? $patientRegistry->isRadioTherapy,
                'attending_doctor' => $request->payload['selectedConsultant'][0]['doctor_code'] ?? $patientRegistry->attending_doctor,
                'attending_doctor_fullname' => $request->payload['selectedConsultant'][0]['doctor_name'] ?? $patientRegistry->attending_doctor_fullname,
                'bmi' => $request->payload['bmi'] ?? $patientRegistry->bmi,
                'weight' => $request->payload['weight'] ?? $patientRegistry->weight,
                'weightUnit' => $request->payload['weightUnit'] ?? $patientRegistry->weightUnit,
                'height' => $request->payload['height'] ?? $patientRegistry->height,
                'heightUnit' => $request->payload['height_Unit'] ?? $patientRegistry->height_Unit,
                'isOpenLateCharges' => $request->payload['LateCharges'] ?? $patientRegistry->LateCharges,
                'mscdisposition_id' => $request->payload['mscdisposition_id'] ?? $patientRegistry->mscdisposition_id,
                'mscCase_result_id' => $request->payload['mscCase_result_id'] ?? $patientRegistry->mscCase_result_id,
                'dateofdeath' => $request->payload['death_date'] ?? $patientRegistry->death_date,
                'isdiedless48Hours' => $request->payload['isdied_less48Hours'] ?? $patientRegistry->isdied_less48Hours,
                'isAutopsy' => $request->payload['isAutopsy'] ?? $patientRegistry->isAutopsy,
                'barcode_image' => $request->payload['barcode_image'] ?? $patientRegistry->barcode_image,
                'barcode_code_id' => $request->payload['barcode_code_id'] ?? $patientRegistry->barcode_code_id,
                'barcode_code_string' => $request->payload['barcode_code_string'] ?? $patientRegistry->barcode_code_string,
                'isWithConsent_DPA' => $request->payload['isWithConsent_DPA'] ?? $patientRegistry->isWithConsent_DPA,
                'registry_remarks' => $request->payload['registry_remarks'] ?? $patientRegistry->registry_remarks, 
                'UpdatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);   
            
            DB::connection('sqlsrv_patient_data')->commit();
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
                $query->where('mscAccount_trans_types', 1);
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
