<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\HIS\mscPatientBroughtBy;
use App\Models\HIS\mscComplaint;
use App\Models\HIS\mscServiceType;
use App\Models\HIS\PatientAllergies;
use App\Models\User;
use App\Helpers\GetIP;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\PatientRegistrationData;
use App\Helpers\HIS\PatientRegistrySequence;

class InpatientRegistrationController extends Controller
{
    //
    protected $check_is_allow_medsys;
    protected $patient_data;
    protected $sequence_number;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->sequence_number = new PatientRegistrySequence();
        $this->patient_data = new PatientRegistrationData();
    }
    
    public function index() {
        try {

            $today = Carbon::now()->format('Y-m-d');
            // $today = '2024-11-26';
            $data = Patient::query();

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5)  
                    ->where('isRevoked', 0)              
                    ->whereDate('registry_Date', $today);
            });

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
                'patientRegistry' => function($query) use ($today) {
                    $query->whereDate('registry_Date', $today)
                          ->where('mscAccount_Trans_Types', 5)
                          ->where('isRevoked', 0)
                          ->with(['allergies' => function($allergyQuery) use ($today) {
                                $allergyQuery->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy')
                                             ->where('isDeleted', '!=', 1)
                                             ->whereDate('created_at', $today);
                          }]);
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

    public function register(Request $request) {

        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            $sequenceNo = $this->sequence_number->handleSequencesCreatePatient($request);

            $patientRule = [
                'lastname'  => $request->payload['lastname'], 
                'firstname' => $request->payload['firstname'],
                'birthdate' => $request->payload['birthdate']
            ];

            $currentTimestamp = Carbon::now();
            $today = Carbon::now()->format('Y-m-d');

            $existingRegistry = PatientRegistry::where('patient_Id', $sequenceNo['patientId'])
                ->whereDate('registry_Date', $today)
                ->exists();
    
                $patient = Patient::updateOrCreate(
                    $patientRule,  
                    $this->patient_data->preparePatientData($request, $checkUser, $currentTimestamp, $sequenceNo['patientId'], $this->handleExistingPatientData($request))
                );
                
                $patient->past_medical_procedures()->create($this->patient_data->preparePastMedicalProcedureData($request, $checkUser, $sequenceNo['patientId'], $existingData = null));
                $patient->past_medical_history()->create($this->patient_data->preparePastMedicalHistoryData($request, $checkUser, $sequenceNo['patientId'], $existingData = null));
                $patient->past_immunization()->create($this->patient_data->preparePastImmunizationData($request, $checkUser, $sequenceNo['patientId'], $existingData = null));
                $patient->past_bad_habits()->create($this->patient_data->preparePastBadHabitsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
    
                $patientPriviledgeCard = $patient->privilegedCard()->create($this->patient_data->patientPrivilegedCardData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                $patientPriviledgeCard->pointTransactions()->create($this->patient_data->patientPrivilegedPointTransactionsData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
                $patientPriviledgeCard->pointTransfers()->create($this->patient_data->patientPrivilegedPointTransferData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
        
                if(!$existingRegistry):
                    $patientRegistry = $patient->patientRegistry()->create($this->patient_data->preparePatientRegistryData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $sequenceNo['erCaseNo'], $existingData=null));
                    $patientRegistry->history()->create($this->patient_data->prepareHistoryData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->immunizations()->create($this->patient_data->preparePatientImmunizationData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->vitals()->create($this->patient_data->prepareVitalSignsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->medical_procedures()->create($this->patient_data->preparePatientMedicalProcedure($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->administered_medicines()->create($this->patient_data->prepareAdministeredMedicineData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->bad_habits()->create($this->patient_data->prepareBadHabitsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->patientDoctors()->create($this->patient_data->preparePatientDoctorsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->pertinentSignAndSymptoms()->create($this->patient_data->preparePatientPertinentSignAndSymptomsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->physicalExamtionChestLungs()->create($this->patient_data->preparePatientPhysicalExamptionChestLungsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->courseInTheWard()->create($this->patient_data->preparePatientCourseInTheWardData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->physicalExamtionCVS()->create($this->patient_data->preparePatientPhysicalExamptionCVSData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->medications()->create($this->patient_data-> patientMedicationsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null ));
                    $patientRegistry->physicalExamtionHEENT()->create($this->patient_data->patientPhysicalExamptionHEENTData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->physicalSkinExtremities()->create($this->patient_data->patientPhysicalSkinExtremitiesData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null ));
                    $patientRegistry->physicalAbdomen()->create($this->patient_data->preparePatientPhysicalAbdomenData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->physicalNeuroExam()->create($this->patient_data->patientPhysicalNeuroExamData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientRegistry->physicalGUIE()->create($this->patient_data->patientPhysicalGUIData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null ));
                    $patientRegistry->PhysicalExamtionGeneralSurvey()->create($this->patient_data->preparePatientPhysicalExamptionGeneralSurveyData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
    
                    $OBG = $patientRegistry->oBGYNHistory()->create($this->patient_data->prepareOBGHistoryData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $OBG->PatientPregnancyHistory()->create($this->patient_data->patientPregnancyHistoryData($request, $checkUser, $OBG->id, $sequenceNo['registryId'], $existingData = null));
                    $OBG->gynecologicalConditions()->create($this->patient_data->patientGynecologicalConditions($request, $checkUser, $OBG->id, $sequenceNo['registryId'], $existingData = null ));
    
                    if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
                        foreach($request->payload['selectedAllergy'] as $allergy) {
    
                            $commonData = [
                                'patient_Id'            => $sequenceNo['patientId'],
                                'case_No'               => $sequenceNo['registryId'],
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
    
                    $patientDischarge = $patientRegistry->dischargeInstructions()->create($this->patient_data->patientDischargeInstructionsData($request, $checkUser, $sequenceNo['patientId'], $sequenceNo['registryId'], $existingData = null));
                    $patientDischarge->dischargeMedications()->create($this->patient_data->patientDischargedMedicationsData($request, $checkUser, $patientDischarge->id, $existingData = null));
                    $patientDischarge->dischargeFollowUpLaboratories()->create($this->patient_data->patientDischargedFollowUpLaboratoriesData($request, $checkUser, $patientDischarge->id, $existingData = null));
                    $patientDischarge->dischargeFollowUpTreatment()->create($this->patient_data->patientDischargedFollowUpTreatmentData($request, $checkUser, $patientDischarge->id, $existingData = null));
                    $patientDischarge->dischargeDoctorsFollowUp()->create($this->patient_data->patientDischargedDoctorsFolloUpData($request, $checkUser, $patientDischarge->id, $existingData = null));
           
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
                    $this->patient_data->preparePatientData($request, $checkUser, $currentTimestamp, $patient_id, $patient)
                );

            endif;

            $existingRegistry = PatientRegistry::where('patient_Id', $patient_id)
                ->whereDate('registry_Date', $today)
                ->exists();

            $getSequence = $this->sequence_number->handleSequenceUpdatePatient($request, $existingRegistry);
            $registry_id = $getSequence['registryId'];
            $er_Case_No  = $getSequence['erCaseNo'];
            $patient =  Patient::updateOrCreate(
                ['patient_Id' => $patient_id], 
                $this->patient_data->preparePatientData($request, $checkUser, $currentTimestamp,  $patient_id, $patient)
            );

                $patientRegistry = $patient->patientRegistry()->whereDate('registry_Date', $today)->first();

                $patientPastDataCond = [
                    'patient_Id'    => $id, 
                ];

                $patientRegistryCond = [
                    'case_No'       => $registry_id,
                ];
                $patient->past_medical_procedures()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastMedicalProcedureData($request, $checkUser, $patient_id, $existingData = null));
                $patient->past_medical_history()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastMedicalHistoryData($request, $checkUser, $patient_id, $existingData = null));
                $patient->past_immunization()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastImmunizationData($request, $checkUser, $patient_id, $existingData = null));
                $patient->past_bad_habits()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->preparePastBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientPriviledgeCard = $patient->privilegedCard()->whereDate('created_at', $today)->updateOrCreate($patientPastDataCond, $this->patient_data->patientPrivilegedCardData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientPriviledgeCard->pointTransactions()->whereDate('created_at', $today)->updateOrCreate(
                    [
                        'card_Id' => $patientPriviledgeCard->id, 
                    ], 
                    $this->patient_data->patientPrivilegedPointTransactionsData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
                $patientPriviledgeCard->pointTransfers()->whereDate('created_at', $today)->updateOrCreate(
                    [
                        'fromCard_Id'   => $patientPriviledgeCard->id,
                    ], 
                    $this->patient_data->patientPrivilegedPointTransferData($request, $checkUser, $patientPriviledgeCard->id, $existingData = null));
               
                $patientRegistry = $patient->patientRegistry()->whereDate('registry_Date', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientRegistryData($request, $checkUser, $patient_id, $registry_id, $er_Case_No, $existingData=null));
                $patientRegistry->history()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->prepareHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->medical_procedures()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientMedicalProcedure($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->vitals()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->prepareVitalSignsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->immunizations()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientImmunizationData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $test = $patientRegistry->administered_medicines()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->prepareAdministeredMedicineData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                if(isset($request->payload['selectedAllergy']) && !empty($request->payload['selectedAllergy'])) {
                    echo $registry_id;
                    $patient_Allergy = $patientRegistry->allergies()->where('case_No', $registry_id)->whereDate('created_at', $today)->first();
                    if($patient_Allergy)
                        $this->updateAllergy($registry_id);
                    
                    foreach ($request->payload['selectedAllergy'] as $allergy) {

                        $commonData = [
                            'patient_Id'            => $patient_id,
                            'case_No'               => $registry_id,
                            'allergy_Type_Id'       => $allergy['allergy_id'],
                            'createdby'             => $allergy->createdby ?? $checkUser->idnumber,
                            'created_at'            => $allergy->created_at ?? Carbon::now(),
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
        
                $patientRegistry->bad_habits()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->prepareBadHabitsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->patientDoctors()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientDoctorsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->pertinentSignAndSymptoms()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPertinentSignAndSymptomsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->physicalExamtionChestLungs()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionChestLungsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->courseInTheWard()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientCourseInTheWardData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->physicalExamtionCVS()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionCVSData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->medications()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data-> patientMedicationsData($request, $checkUser, $patient_id, $registry_id, $existingData = null ));
                $patientRegistry->physicalExamtionHEENT()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond,$this->patient_data->patientPhysicalExamptionHEENTData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->physicalSkinExtremities()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalSkinExtremitiesData($request, $checkUser, $patient_id, $registry_id, $existingData = null ));
                $patientRegistry->physicalAbdomen()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalAbdomenData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->physicalNeuroExam()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalNeuroExamData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->physicalGUIE()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->patientPhysicalGUIData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $patientRegistry->PhysicalExamtionGeneralSurvey()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->preparePatientPhysicalExamptionGeneralSurveyData($request, $checkUser, $patient_id, $registry_id, $existingData = null));

                $OBG = $patientRegistry->oBGYNHistory()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->prepareOBGHistoryData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $obgyneCond = [
                    'OBGYNHistoryID' => $OBG->id,
                ];
                $OBG->PatientPregnancyHistory()->whereDate('created_at', $today)->updateOrCreate($obgyneCond, $this->patient_data->patientPregnancyHistoryData($request, $checkUser, $OBG->id, $registry_id, $existingData = null));
                $OBG->gynecologicalConditions()->whereDate('created_at', $today)->updateOrCreate($obgyneCond, $this->patient_data->patientGynecologicalConditions($request, $checkUser, $OBG->id, $registry_id, $existingData = null ));

                $patientDischarge = $patientRegistry->dischargeInstructions()->whereDate('created_at', $today)->updateOrCreate($patientRegistryCond, $this->patient_data->patientDischargeInstructionsData($request, $checkUser, $patient_id, $registry_id, $existingData = null));
                $dischargedCond = [
                    'instruction_Id' => $patientDischarge->id,
                ];
                $patientDischarge->dischargeMedications()->whereDate('created_at', $today)->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedMedicationsData($request, $checkUser, $patientDischarge->id, $existingData = null));
                $patientDischarge->dischargeFollowUpLaboratories()->whereDate('created_at', $today)->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedFollowUpLaboratoriesData($request, $checkUser, $patientDischarge->id, $existingData = null));
                $patientDischarge->dischargeFollowUpTreatment()->whereDate('created_at', $today)->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedFollowUpTreatmentData($request, $checkUser, $patientDischarge->id, $existingData = null));
                $patientDischarge->dischargeDoctorsFollowUp()->whereDate('created_at', $today)->updateOrCreate($dischargedCond, $this->patient_data->patientDischargedDoctorsFolloUpData($request, $checkUser, $patientDischarge->id, $existingData = null));

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


    public function getrevokedinpatient() {
        try {
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $today = Carbon::now()->format('Y-m-d');

            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_trans_types', 6);
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
}
