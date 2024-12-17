<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\mscPatientBroughtBy;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Models\HIS\mscComplaint;
use App\Models\HIS\mscServiceType;
use App\Models\HIS\PatientAllergies;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetIP;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\PatientRegistrationData;
use App\Helpers\HIS\PatientRegistrySequence;

class RegistrationController extends Controller
{
    protected $check_is_allow_medsys;
    protected $patient_data;
    protected $sequence_number;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->sequence_number = new PatientRegistrySequence();
        $this->patient_data = new PatientRegistrationData();
    }
    public function register(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {
            if(intval($request->payload['mscAccount_Trans_Types']) === 5) {
                echo 'test napud';
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if(!$checkUser):
                    return response()->json([$message='Incorrect Username or Password'], 404);
                endif;
            }
            if(intval($request->payload['mscAccount_Trans_Types']) === 5) {
                $sequenceNo = $this->sequence_number->handleEmergencyRegisterPatientSequences();
            } else {
                $sequenceNo = $this->sequence_number->handleInPatientRegisterPatientSequences();
            }
            $registerPatient = $this->registerPatient($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $sequenceNo['erCaseNo']);
            if($registerPatient) {
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_patient_data')->commit();
                DB::connection('sqlsrv')->commit();
            }
            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' =>  $registerPatient['patient'],
                'patientRegistry' => $registerPatient['patientRegistry']
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
            $checkUser = '';
            $accountType = $request->payload['mscAccount_Trans_Types'];
            if(intval($accountType) === 5) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if(!$checkUser):
                    return response()->json([$message='Incorrect Username or Password'], 404);
                endif;
            }
            $today = Carbon::now();
            $existingRegistry = $this->patient_data->handleExistingRegistryData($id, $today);
            if(!$existingRegistry) {
                if(intval($request->payload['mscAccount_Trans_Types']) === 5) {
                    $sequenceNo  = $this->sequence_number->handleUpdateEmergencyPatientSequences();
                    $registry_id = $sequenceNo['registryId'];
                    $er_Case_No  = $sequenceNo['erCaseNo'];
                } else {
                    $sequenceNo = $this->sequence_number->handleInPatientUpdateSequences();
                    $registry_id = $sequenceNo['registryId'];
                    $er_Case_No  = null;
                }
            } else {
                $registry_id = $request->payload['case_No'];
                $er_Case_No = $request->payload['er_Case_No'] ?? null;
            }
            $registerPatient = $this->registerPatient($request, $checkUser, $id, $registry_id, $er_Case_No);
            if($registerPatient) {
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_patient_data')->commit();
                DB::connection('sqlsrv')->commit();
            }
            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' =>  $registerPatient['patient'],
                'patientRegistry' => $registerPatient['patientRegistry']
            ], 201);
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

    private function registerPatient($request, $checkUser, $patient_id, $registry_id, $er_Case_No) {
        $patientRule = [
            'lastname'  => $request->payload['lastname'], 
            'firstname' => $request->payload['firstname'],
            'birthdate' => $request->payload['birthdate']
        ];
        $patientPastDataCond = [
            'patient_Id'    => $patient_id, 
        ];

        $patientRegistryCond = [
            'patient_Id'    => $patient_id,
            'case_No'       => $registry_id,
        ];
        $currentTimestamp = Carbon::now();
        $today = Carbon::now()->format('Y-m-d');
        $patient = Patient::updateOrCreate($patientRule, $this->patient_data->preparePatientData($request, $checkUser, $currentTimestamp, $patient_id, $this->patient_data->handleExistingPatientData($request->payload['lastname'], $request->payload['firstname'])));
        echo $patient;
        $patient->past_medical_procedures()->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastMedicalProcedureData($request, $checkUser, $patient_id, $existingData = null));
        $patient->past_medical_history()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastMedicalHistoryData($request, $checkUser, $patient_id, $existingData = null));
        $patient->past_immunization()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastImmunizationData($request, $checkUser, $patient_id, $existingData = null));
        $patient->past_bad_habits()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientPriviledgeCard = $patient->privilegedCard()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->patientPrivilegedCardData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientPriviledgeCard->pointTransactions()->whereDate('created_at', $today)->updateOrCreate(['card_Id' => $patientPriviledgeCard->id], $this->patient_data->patientPrivilegedPointTransactionsData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
        $patientPriviledgeCard->pointTransfers()->whereDate('created_at', $today)->updateOrCreate(['fromCard_Id' => $patientPriviledgeCard->id], $this->patient_data->patientPrivilegedPointTransferData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
        $patientRegistry = $patient->patientRegistry()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientRegistryData($request, $checkUser, $patient_id, $registry_id, $er_Case_No, $existingData=null));
        $patientRegistry->history()->updateOrCreate($patientRegistryCond, $this->patient_data->prepareHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->immunizations()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientImmunizationData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->vitals()->updateOrCreate($patientRegistryCond, $this->patient_data->prepareVitalSignsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->medical_procedures()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientMedicalProcedure($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->administered_medicines()->updateOrCreate($patientRegistryCond, $this->patient_data->prepareAdministeredMedicineData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->bad_habits()->updateOrCreate($patientRegistryCond, $this->patient_data->prepareBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->patientDoctors()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientDoctorsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        // if(isset($request->payload['selectedConsultant']) && !empty($request->payload['selectedConsultant'])) {
        //     $this->processPatientDoctors($request, $checkUser, $patient_id, $registry_id, $patientRegistry);
        // }
        $patientRegistry->patientDoctors()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientDoctorsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->pertinentSignAndSymptoms()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPertinentSignAndSymptomsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->physicalExamtionChestLungs()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionChestLungsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->courseInTheWard()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientCourseInTheWardData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->physicalExamtionCVS()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionCVSData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->medications()->updateOrCreate($patientRegistryCond, $this->patient_data-> patientMedicationsData($request, $checkUser, $patient_id, $registry_id, $existingData = null ));
        $patientRegistry->physicalExamtionHEENT()->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalExamptionHEENTData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->physicalSkinExtremities()->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalSkinExtremitiesData($request, $checkUser, $patient_id, $registry_id, $existingData = null ));
        $patientRegistry->physicalAbdomen()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalAbdomenData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->physicalNeuroExam()->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalNeuroExamData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $patientRegistry->physicalGUIE()->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalGUIData($request, $checkUser, $patient_id, $registry_id, $existingData = null ));
        $patientRegistry->PhysicalExamtionGeneralSurvey()->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionGeneralSurveyData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $OBG = $patientRegistry->oBGYNHistory()->updateOrCreate($patientRegistryCond, $this->patient_data->prepareOBGHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $obgyneCond = ['OBGYNHistoryID' => $OBG->id];
        $OBG->PatientPregnancyHistory()->updateOrCreate($obgyneCond, $this->patient_data->patientPregnancyHistoryData($request, $checkUser, $OBG->id, $registry_id, $existingData = null));
        $OBG->gynecologicalConditions()->updateOrCreate($obgyneCond, $this->patient_data->patientGynecologicalConditions($request, $checkUser, $OBG->id, $registry_id, $existingData = null ));
        if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
            $this->processAllergy($request, $checkUser, $patient_id, $registry_id, $patientRegistry, $today);
        }
        $patientDischarge = $patientRegistry->dischargeInstructions()->updateOrCreate($patientRegistryCond, $this->patient_data->patientDischargeInstructionsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
        $dischargedCond = ['instruction_Id' => $patientDischarge->id];
        $patientDischarge->dischargeMedications()->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedMedicationsData($request, $checkUser, $patientDischarge->id, $existingData = null));
        $patientDischarge->dischargeFollowUpLaboratories()->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedFollowUpLaboratoriesData($request, $checkUser, $patientDischarge->id, $existingData = null));
        $patientDischarge->dischargeFollowUpTreatment()->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedFollowUpTreatmentData($request, $checkUser, $patientDischarge->id, $existingData = null));
        $patientDischarge->dischargeDoctorsFollowUp()->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedDoctorsFolloUpData($request, $checkUser, $patientDischarge->id, $existingData = null));
       
        if(!$patient || !$patientRegistry):
            echo 'Failed Here';
            throw new \Exception('Error');
        else: 
            return [
                'patient' => $patient,
                'patientRegistry' => $patientRegistry
            ];
        endif;
    }

    private function processAllergy($request, $checkUser, $patient_id, $registry_id, $patientRegistry, $today) { 
        if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
            $patient_Allergy = $patientRegistry->allergies()->where('case_No', $registry_id)->whereDate('created_at', $today)->first();
            if($patient_Allergy) {
                $this->updateAllergy($registry_id);
            }
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
    }
    private function processPatientDoctors($request, $checkUser, $patient_id, $registry_id, $patientRegistry) {
       if(isset($request->payload['selectedConsultant']) && !empty($request->payload['selectedConsultant'])) {
        foreach($request->payload['selectedConsultant'] as $consultant) {
            $patientRegistry->patientDoctors()->where('case_No', $registry_id)->updateOrCreate(
                [
                    'doctor_Id' => $consultant['attending_Doctor']
                ], $this->patient_data->preparePatientDoctorsData($consultant, $checkUser, $patient_id, $registry_id, $existingData = null));
        }
       }
    }
    private function updateAllergy($registry_id) {
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
}
