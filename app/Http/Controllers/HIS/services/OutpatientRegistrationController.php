<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutpatientRegistrationController extends Controller
{
    public function index() {
        try { 
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'patientRegistry');
            $data->whereHas('patientRegistry', function($query) {
                $query->where('mscAccount_trans_types', 1); // Patients that are outpatients only
                if(Request()->keyword) {
                    $query->where(function($subQuery) {
                        $subQuery->where('lastname', 'LIKE', '%'.Request()->keyword.'%') 
                                ->orWhere('firstname', 'LIKE', '%'.Request()->keyword.'%') 
                                ->orWhere('patient_id', 'LIKE', '%'.Request()->keyword.'%');
                    });
                }
            });
            $data->orderBy('id', 'asc');
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
        DB::beginTransaction();
        try {
            $sequence = SystemSequence::where('code','MPID')->first();
            $patient_id = $request->payload['patient_id'] ?? $sequence->seq_no;
            
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

            $patient = Patient::updateOrCreate(
                [
                    'lastname' => $request->payload['lastname'],
                    'firstname' => $request->payload['firstname'], 
                ],
                [
                    'patient_id' => $patient_id,
                    'title_id' => $request->payload['title_id'] ?? null,
                    'lastname' => ucwords($request->payload['lastname']),
                    'firstname' => ucwords($request->payload['firstname']),
                    'middlename' => ucwords($request->payload['middlename'] ?? null),
                    'suffix_id' => $request->payload['suffix_id'] ?? null,
                    'birthdate' => $request->payload['birthdate'],
                    'age' => $request->payload['age'] ?? null,
                    'sex_id' => $request->payload['sex_id'],
                    'nationality_id' => $request->payload['nationality_id'] ?? null,
                    'religion_id' => $request->payload['religion_id'] ?? null,
                    'civilstatus_id' => $request->payload['civilstatus_id'],
                    'typeofbirth_id' => $request->payload['typeofbirth_id'] ?? null,
                    'birthtime' => $request->payload['birthtime'] ?? null,
                    'typeofdeath_id' => $request->payload['typeofdeath_id'] ?? null,
                    'timeofdeath' => $request->payload['timeofdeath'] ?? null,
                    'bloodtype_id' => $request->payload['bloodtype_id'] ?? null,
                    'bldgstreet' => $request->payload['address']['bldgstreet'] ?? null,
                    'region_id' => $request->payload['address']['region_id'] ?? null,
                    'province_id' => $request->payload['address']['province_id'] ?? null,
                    'municipality_id' => $request->payload['address']['municipality_id'] ?? null,
                    'barangay_id' => $request->payload['address']['barangay_id'] ?? null,
                    'zipcode_id' => $request->payload['address']['zipcode_id'] ?? null,
                    'country_id' => $request->payload['address']['country_id'] ?? null,
                    'occupation' => $request->payload['occupation'] ?? null,
                    'telephone_number' => $request->payload['telephone_number'] ?? null,
                    'mobile_number' => $request->payload['mobile_number'] ?? null,
                    'email_address' => $request->payload['email_address'] ?? null,
                    'isSeniorCitizen' => $request->payload['isSeniorCitizen'] ?? false,
                    'SeniorCitizen_ID_Number' => $request->payload['SeniorCitizen_ID_Number'] ?? null,
                    'isPWD' => $request->payload['isPWD'] ?? false,
                    'PWD_ID_Number' => $request->payload['PWD_ID_Number'] ?? null,
                    'isPhilhealth_Member' => $request->payload['isPhilhealth_Member'] ?? false,
                    'Philhealth_Number' => $request->payload['Philhealth_Number'] ?? null,
                    'isEmployee' => $request->payload['isEmployee'] ?? false,
                    'branch_id' => $request->payload['branch_id'] ?? 1,
                    'GSIS_Number' => $request->payload['GSIS_Number'] ?? null,
                    'SSS_Number' => $request->payload['SSS_Number'] ?? null,
                    'is_old_patient' => $request->payload['is_old_patient'] ?? false,
                    'portal_access_uid' => $request->payload['portal_access_uid'] ?? null,
                    'portal_access_pwd' => $request->payload['portal_access_pwd'] ?? null,
                    'isBlacklisted' => $request->payload['isBlacklisted'] ?? false,
                    'blacklist_reason' => $request->payload['blacklist_reason'] ?? null,
                    'isAbscond' => $request->payload['isAbscond'] ?? false,
                    'abscond_details' => $request->payload['abscond_details'] ?? null,
                    'createdBy' => Auth()->user()->idnumber,
                    'updatedBy' => Auth()->user()->idnumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            $patientRegistry = PatientRegistry::updateOrCreate(
                [
                    'patient_id' => $patient_id,
                ],
                [
                    'mscBranches_id' => $request->payload['mscBranches_id'] ?? 1,
                    'register_id_no' => $request->payload['register_id_no'] ?? null,
                    'register_source' => $request->payload['register_source'] ?? null,
                    'register_type' => $request->payload['register_type'] ?? null, // Should not be null in the future
                    'register_source_case_no' => $request->payload['register_source_case_no'] ?? null,
                    'mscAccount_type' => $request->payload['mscAccount_type'] ?? '',
                    'mscAccount_discount_id' => $request->payload['mscAccount_discount_id'] ?? null,
                    'mscAccount_trans_types' => $request->payload['mscAccount_trans_types'], 
                    'mscPatient_category' => $request->payload['mscPatient_category'] ?? null,
                    'mscPrice_Groups' => $request->payload['mscPrice_Groups'],
                    'mscPrice_Schemes' => $request->payload['mscPrice_Schemes'],
                    'mscService_type' => $request->payload['mscService_type'] ?? null,
                    'mscService_type2' => $request->payload['mscService_type2'] ?? null,
                    'mscpatient_type' => $request->payload['mscpatient_type'] ?? null,
                    'queue_number' => $request->payload['queue_number'] ?? null,
                    'arrived_date' => $request->payload['arrived_date'] ?? null,
                    'registry_userid' => Auth()->user()->idnumber,
                    'registry_date' => now(),
                    'registry_status' => $request->payload['registry_status'] ?? null,
                    'registry_department_id' => $request->payload['registry_department_id'] ?? null,
                    'discharged_userid' => $request->payload['discharged_userid'] ?? null,
                    'discharged_date' => $request->payload['discharged_date'] ?? null,
                    'billed_userid' => $request->payload['billed_userid'] ?? null,
                    'billed_date' => $request->payload['billed_date'] ?? null,
                    'billed_remarks' => $request->payload['billed_remarks'] ?? null,
                    'mgh_userid' => $request->payload['mgh_userid'] ?? null,
                    'mgh_datetime' => $request->payload['mgh_datetime'] ?? null,
                    'untag_mgh_userid' => $request->payload['untag_mgh_userid'] ?? null,
                    'untag_mgh_datetime' => $request->payload['untag_mgh_datetime'] ?? null,
                    'isHoldReg' => $request->payload['isHoldReg'] ?? false,
                    'hold_userid' => $request->payload['hold_userid'] ?? null,
                    'hold_no' => $request->payload['hold_no'] ?? null,
                    'hold_date' => $request->payload['hold_date'] ?? null,
                    'hold_remarks' => $request->payload['hold_remarks'] ?? null,
                    'isRevoked' => $request->payload['isRevoked'] ?? false,
                    'revokedBy' => $request->payload['revokedBy'] ?? null,
                    'revoked_date' => $request->payload['revoked_date'] ?? null,
                    'revoked_remarks' => $request->payload['revoked_remarks'] ?? null,
                    'guarantor_id' => $request->payload['selectedGuarantor'][0]['guarantor_code'] ?? null,
                    'guarantor_name' => $request->payload['selectedGuarantor'][0]['guarantor_name'] ?? null,
                    'guarantor_approval_code' => $request->payload['selectedGuarantor'][0]['guarantor_approval_code'] ?? null,
                    'guarantor_approval_no' => $request->payload['selectedGuarantor'][0]['guarantor_approval_no'] ?? null,
                    'guarantor_approval_date' => $request->payload['selectedGuarantor'][0]['guarantor_approval_date'] ?? null,
                    'guarantor_validity_date' => $request->payload['selectedGuarantor'][0]['guarantor_validity_date'] ?? null,
                    'guarantor_approval_remarks' => $request->payload['guarantor_approval_remarks'] ?? null,
                    'isWithCreditLimit' => !empty($request->payload['selectedGuarantor'][0]['guarantor_code']) ? true : ($request->payload['isWithCreditLimit'] ?? false),
                    'guarantor_credit_Limit' => $request->payload['selectedGuarantor'][0]['guarantor_credit_Limit'] ?? null,
                    'isWithPhilHealth' => $request->payload['isWithPhilHealth'] ?? false,
                    'msc_PHIC_Memberships' => $request->payload['msc_PHIC_Memberships'] ?? null,
                    'philhealth_number' => $request->payload['philhealth_number'] ?? null,
                    'isWithMedicalPackage' => $request->payload['isWithMedicalPackage'] ?? false,
                    'Medical_Package_id' => $request->payload['Medical_Package_id'] ?? null,
                    'Medical_Package_name' => $request->payload['Medical_Package_name'] ?? null,
                    'Medical_Package_amount' => $request->payload['Medical_Package_amount'] ?? null,
                    'clinical_chief_complaint' => $request->payload['clinical_chief_complaint'] ?? null,
                    'impression' => $request->payload['impression'] ?? null,
                    'isCriticallyIll' => $request->payload['isCriticallyIll'] ?? false,
                    'illness_type' => $request->payload['illness_type'] ?? null,
                    'isreferredfrom' => $request->payload['isreferredfrom'] ?? false,
                    'referred_from_HCI' => $request->payload['referred_from_HCI'] ?? null,
                    'referred_from_HCI_address' => $request->payload['referred_from_HCI_address'] ?? null,
                    'referred_from_HCI_code' => $request->payload['referred_from_HCI_code'] ?? null,
                    'referring_doctor' => $request->payload['referring_doctor'] ?? null,
                    'isHemodialysis' => $isHemodialysis,
                    'isPeritoneal' => $isPeritoneal,
                    'isLINAC' => $isLINAC,
                    'isCOBALT' => $isCOBALT,
                    'isBloodTrans' => $isBloodTrans,
                    'isChemotherapy' => $isChemotherapy,
                    'isBrachytherapy' => $isBrachytherapy,
                    'isDebridement' => $isDebridement,
                    'isTBDots' => $isTBDots,
                    'isPAD' => $isPAD,
                    'isRadioTherapy' => $isRadioTherapy,
                    'attending_doctor' => $request->payload['selectedConsultant'][0]['doctor_code'] ?? null,
                    'attending_doctor_fullname' => $request->payload['selectedConsultant'][0]['doctor_name'] ?? null,
                    'mscDispositions' => $request->payload['mscDispositions'] ?? null,
                    'mscAdmResults' => $request->payload['mscAdmResults'] ?? null,
                    'mscDeath_Types' => $request->payload['mscDeath_Types'] ?? null,
                    'bmi' => $request->payload['bmi'] ?? null,
                    'weight' => $request->payload['weight'] ?? null,
                    'weightUnit' => $request->payload['weightUnit'] ?? null,
                    'height' => $request->payload['height'] ?? null,
                    'height_Unit' => $request->payload['height_Unit'] ?? null,
                    'voucher_no' => $request->payload['voucher_no'] ?? null,
                    'appt_Trans' => $request->payload['appt_Trans'] ?? null,
                    'LateCharges' => $request->payload['LateCharges'] ?? null,
                    'mscdisposition_id' => $request->payload['mscdisposition_id'] ?? null,
                    'mscCase_result_id' => $request->payload['mscCase_result_id'] ?? null,
                    'mscDeath_types_id' => $request->payload['mscDeath_types_id'] ?? null,
                    'death_date' => $request->payload['death_date'] ?? null,
                    'mscDelivery_types_id' => $request->payload['mscDelivery_types_id'] ?? null,
                    'isdied_less48Hours' => $request->payload['isdied_less48Hours'] ?? false,
                    'isAutopsy' => $request->payload['isAutopsy'] ?? false,
                    'barcode_image' => $request->payload['barcode_image'] ?? null,
                    'barcode_code_id' => $request->payload['barcode_code_id'] ?? null,
                    'barcode_code_string' => $request->payload['barcode_code_string'] ?? null,
                    'isWithConsent_DPA' => $request->payload['isWithConsent_DPA'] ?? false,
                    'registry_remarks' => $request->payload['registry_remarks'] ?? null, 
                    'CreatedBy' => Auth()->user()->idnumber,
                    'created_at' => now(),
                    'UpdatedBy' => Auth()->user()->idnumber,
                    'updated_at' => now(),
                ]
            );
            $sequence->update([
                'seq_no' => $sequence->seq_no + 1,
                'recent_generated' => $sequence->seq_no,
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Outpatient data registered successfully',
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
