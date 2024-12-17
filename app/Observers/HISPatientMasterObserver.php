<?php

namespace App\Observers;

use App\Models\HIS\services\Patient;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysPatientMaster2;
use DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\HIS\SysGlobalSetting;
use Illuminate\Support\Arr;
class HISPatientMasterObserver
{
    /**
     * Handle the Patient "created" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    protected $check_is_allow_medsys;
    protected $connection;
     public function __construct() {
        
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
     }
    public function created(Patient $patient){
        try {
        
            if($this->check_is_allow_medsys) {

                $spName         = ($patient->spLastname || $patient->spFirstname) 
                                ? $patient->spLastname . ', ' . $patient->spFirstname . ' ' . $patient->spMiddlename
                                : '';

                $motherName     = ($patient->motherLastname || $patient->motherFirstname)
                                ? $patient->motherLastname . ', ' . $patient->motherFirstname . ' ' . $patient->motherMiddlename
                                : '';

                $fatherName     = ($patient->fatherLastname || $patient->fatherFirstname)
                                ? $patient->fatherLastname . ', ' . $patient->fatherFirstname . ' ' . $patient->fatherMiddlename
                                : '';

                $patient_Data = [
                    'HospNum'       => $patient->patient_Id   ?? '',
                    'LastName'      => $patient->lastname     ?? '',
                    'FirstName'     => $patient->firstname    ?? '',
                    'MiddleName'    => $patient->middlename   ?? '',
                    'AccountNum'    => '',
                    'HouseStreet'   => $patient->bldgstreet   ?? '',
                    'Barangay'      => $patient->barangay_id  ?? '',
                    'Sex'           => $patient->sex_id       ?? '',
                    'BirthDate'     => $patient->birthdate    ?? '',
                    'CivilStatus'   => $patient->civil_Status ?? '',
                    'Age'           => $patient->patient_Age  ?? ''
                ];

                $pstient_Data_Master2 = [
                    
                    'HospNum'           => $patient->patient_Id,
                    'BirthPlace'        => $patient->birthplace,
                    'NationalityID'      => $patient->nationality_id,
                    'ReligionID'        => $patient->religion_id,
                    'Spouse'            => $spName,
                    'SpouseAddress'     => $patient->spAddress ?? ' ',
                    'SpouseTelNum'      => $patient->sptelephone_number,
                    'Father'            => $fatherName,
                    'FatherAddress'     => $patient->fatherAddress ?? ' ',
                    'FatherTelNum'      => $patient->fathertelephone_number,
                    'Mother'            => $motherName,
                    'MotherAddress'     => $patient->motherAddress,
                    'MotherTelNum'      => $patient->mothertelephone_number,
                ];

                MedsysPatientMaster::updateOrCreate(['HospNum' => $patient->patient_Id],$patient_Data);
                MedsysPatientMaster2::updateOrCreate(['HospNum' => $patient->patient_Id], $pstient_Data_Master2);
            }
        } catch (\Exception $e) {

            Log::error('Failed to insert patient into Medsys: ' . $e->getMessage());

            throw new \Exception('Failed to insert patient into Medsys: ' . $e->getMessage());
        }

    }

    /**
     * Handle the Patient "updated" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function updated(Patient $patient)
    {
        try {

            if($this->check_is_allow_medsys && $patient) {
                
                $patientInfo    = MedsysPatientMaster::findOrFail($patient->patient_Id);
                $patientInfo2   = MedsysPatientMaster2::findOrFail($patient->patient_Id);
                if($patientInfo && $patientInfo2) {
                    $spName     = ($patient->spLastname || $patient->spFirstname)
                                ? $patient->spLastname      . ', ' . $patient->spFirstname      . ' ' . $patient->spMiddlename
                                : '';

                    $motherName = ($patient->motherLastname || $patient->motherFirstname)
                                ? $patient->motherLastname  . ', ' . $patient->motherFirstname  . ' ' . $patient->motherMiddlename
                                : '';

                    $fatherName = ($patient->fatherLastname || $patient->fatherFirstname)
                                ? $patient->fatherLastname  . ', ' . $patient->fatherFirstname  . ' ' . $patient->fatherMiddlename
                                : '';

                    $patient_Data = [

                        'LastName'      => $patient->lastname       ?? $patientInfo->LastName,
                        'FirstName'     => $patient->firstname      ?? $patientInfo->FirstName,
                        'MiddleName'    => $patient->middlename     ?? $patientInfo->MiddleName,
                        'HouseStreet'   => $patient->bldgstreet     ?? $patientInfo->HouseStreet,
                        'Barangay'      => $patient->barangay_id    ?? $patientInfo->Barangay,
                        'Sex'           => $patient->sex_id         ?? $patientInfo->Sex,
                        'BirthDate'     => $patient->birthdate      ?? $patientInfo->BirthDate,
                        'CivilStatus'   => $patient->civilstatus_id ?? $patientInfo->CivilStatus,
                        'Age'           => $patient->age            ?? $patientInfo->Age

                    ];

                    $pstient_Data_Master2 = [
                        'BirthPlace'        => $patient->birthplace             ?? $patientInfo2->BirthPlace,
                        'NationalityID'      => $patient->nationality_id        ?? $patientInfo2->NatinalityID,
                        'ReligionID'        => $patient->religion_id            ?? $patientInfo2->ReligionID,
                        'Spouse'            => $spName                          ?? $patientInfo2->Spouse,
                        'SpouseAddress'     => $patient->spAddress              ?? $patientInfo2->SpouseAddress,
                        'SpouseTelNum'      => $patient->sptelephone_number     ?? $patientInfo2->SpouseTelNum,
                        'Father'            => $fatherName                      ?? $patientInfo2->Father,
                        'FatherAddress'     => $patient->fatherAddress          ?? $patientInfo2->fatherAddress,
                        'FatherTelNum'      => $patient->fathertelephone_number ?? $patientInfo2->FatherTelNum,
                        'Mother'            => $motherName                      ?? $patientInfo2->Mother,
                        'MotherAddress'     => $patient->motherAddress          ?? $patientInfo2->MotherAddress,
                        'MotherTelNum'      => $patient->mothertelephone_number ?? $patientInfo2->MotherTelNum,
                    ];

                    MedsysPatientMaster::where('HospNum', $patient->patient_Id)->update( $patient_Data);
                    MedsysPatientMaster2::where('HospNum', $patient->patient_Id)->update($pstient_Data_Master2);

                    Log::info('Patient data from Medsys updated  successfully.');

                    Log::info('Medsys transaction committed successfully.');

                } else {

                    Log::info('Cannot find Patient with this Patient ID. : '. $patient->patient_Id);
                }

            } else {

                Log::error('Failed to update patient data: is either the patient returned is empty or insufficient permissions.');
            }

        } catch (\Exception $e) {

            Log::error('Failed to update patient data from Medsys: ' . $e->getMessage());

            throw new \Exception('Failed to update patient into Medsys: ' . $e->getMessage());
        }

    }

    /**
     * Handle the Patient "deleted" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function deleted(Patient $patient)
    {
        //
    }

    /**
     * Handle the Patient "restored" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function restored(Patient $patient)
    {
        //
    }

    /**
     * Handle the Patient "force deleted" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function forceDeleted(Patient $patient)
    {
        //
    }
}
