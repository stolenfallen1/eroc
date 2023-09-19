<?php

namespace App\Observers;

use DB;
use Log;
use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\MedsysGuarantor;
use App\Helpers\HIS\Medsys_SeriesNo;
use App\Models\HIS\MedsysOutpatient;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysPatientAllergies;
use App\Models\HIS\MedsysPatientInformant;
use App\Models\HIS\MedsysPatientOPDHistory;

class PatientObserver
{
    protected $check_is_allow_medsys;
    public function __construct()
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    /**
     * Handle the Patients "created" event.
     *
     * @param  \App\Models\HIS\Patients  $patients
     * @return void
     */
    public function created(PatientMaster $patients)
    {

        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        try {

            if($this->check_is_allow_medsys) {
                // generated_medsys_patient_id_no = HOSPITAL NO
                // generated_medsys_hospital_opd_no  = OPD

                // generate series number

                $new_medsyspatientMaster = MedsysPatientMaster::where('HospNum', $patients->previous_patient_id)->first();
                if(!$new_medsyspatientMaster) {
                    $new_medsyspatientMaster = MedsysPatientMaster::create([
                        'HospNum' => $patients->previous_patient_id,
                        'AccountNum' => $patients->patient_registry_details->guarantor_id,
                        'Title' => $patients->title_id,
                        'FirstName' => $patients->firstname ?? '',
                        'LastName' => $patients->lastname ?? '',
                        'MiddleName' => $patients->middlename ?? '',
                        'BirthDate' => $patients->birthdate ?? '',
                        'Age' => $patients->age ?? '',
                        'BirthPlace' => $patients->birthplace ?? '',
                        'Barangay' => $patients->barangay_id ?? '',
                        'Housestreet' => $patients->bldgstreet ?? '',
                        'ZipCode' => $patients->zipcode_id ?? '',
                        'Sex' => $patients->sex_id == 1 ? 'M' : 'F',
                        'CivilStatus' => $patients->civilstatus->map_item_id,
                        'Email' => $patients->email_address ?? '',
                        'EmailAddress' => $patients->email_address ?? '',
                        'CellNum' => $patients->mobile_number ?? '',
                        'SeniorCitizenID' => $patients->SeniorCitizen_ID_Number ?? '',
                        'SeniorCitizen' => $patients->SeniorCitizen_ID_Number ? true : false,
                        'Occupation' =>  '',
                        'BloodType' =>  '',
                        'AccountNum' => $patients->guarantor_id ?? ''
                    ]);
                }
                $isOtherDetailsExist =  $new_medsyspatientMaster->patientmaster_other_details()->where('HospNum', $patients->previous_patient_id)->first();
                if(!$isOtherDetailsExist) {
                    $new_medsyspatientMaster->patientmaster_other_details()->create([
                        'HospNum' => $patients->previous_patient_id,
                        'BirthPlace' => $patients->birthplace,
                        'NationalityID' => $patients->nationality->map_item_id,
                        'ReligionID' =>  $patients->religion->map_item_id,
                    ]);
                } else {
                    $new_medsyspatientMaster->patientmaster_other_details()->where('HospNum', $patients->previous_patient_id)->update([
                      'HospNum' => $patients->previous_patient_id,
                      'BirthPlace' => $patients->birthplace,
                      'NationalityID' => $patients->nationality->map_item_id,
                      'ReligionID' =>  $patients->religion->map_item_id,
                    ]);
                }


                $hospitalPlan = 'P';
                if($patients->patient_registry_details->mscAccount_type != '1') {
                    $hospitalPlan = 'C';
                }

                $isIndustrialPatient = '';
                if($patients->patient_registry_details->mscAccount_trans_types == 5) {
                    $isIndustrialPatient = 'Y';
                }
                $ismedsysOPDExists = $new_medsyspatientMaster->opd_registry()->where('IDNum', $patients->patient_registry_details->medsys_idnum)->where('HospNum', $patients->previous_patient_id)->first();
                if(!$ismedsysOPDExists) {
                    $new_medsyspatientMaster->opd_registry()->create([
                        'HospNum' => $patients->previous_patient_id,
                        'IDNum' => $patients->patient_registry_details->medsys_idnum,
                        'AccountNum' => $patients->patient_registry_details->guarantor_id,
                        'AdmDate' => $patients->patient_registry_details->registry_date,
                        'Age' => $patients->age,
                        'SeniorCitizenID' => $patients->SeniorCitizen_ID_Number ?? null,
                        'SeniorCitizen' => $patients->isSeniorCitizen ?? 0,
                        'IsRadiotherapy' => $patients->patient_registry_details->isRadioTherapy,
                        'IsChemo' => $patients->patient_registry_details->isChemotherapy,
                        'IsHemodialysis' => $patients->patient_registry_details->isHemodialysis,
                        'DOTS' => $patients->patient_registry_details->isTBDots,
                        'PAD' => $patients->patient_registry_details->isPAD,
                        'HosPlan' => $hospitalPlan,
                        'PackageID' => '',
                        'CashBasis' => '',
                        'CreditLine' => $patients->patient_registry_details->guarantor_credit_Limit,
                        'PatientType' => '',
                        'OPDStatus' => '',
                        'IndustrialPatient' => $isIndustrialPatient,
                        'HMOApprovalNum' => $patients->patient_registry_details->guarantor_approval_no,
                        'LOANum' => $patients->patient_registry_details->guarantor_approval_code,
                        'Host_Name' => $patients->patient_registry_details->registry_hostname,
                        'UserID' => $patients->patient_registry_details->registry_userid,
                    ]);
                } else {
                    $new_medsyspatientMaster->opd_registry()->where('IDNum', $patients->patient_registry_details->medsys_idnum)->where('HospNum', $patients->previous_patient_id)->update([
                        'HospNum' => $patients->previous_patient_id,
                        'IDNum' => $patients->patient_registry_details->medsys_idnum,
                        'AccountNum' => $patients->patient_registry_details->guarantor_id,
                        'AdmDate' => $patients->patient_registry_details->registry_date,
                        'Age' => $patients->age,
                        'SeniorCitizenID' => $patients->SeniorCitizen_ID_Number ?? null,
                        'SeniorCitizen' => $patients->isSeniorCitizen ?? 0,
                        'IsRadiotherapy' => $patients->patient_registry_details->isRadioTherapy,
                        'IsChemo' => $patients->patient_registry_details->isChemotherapy,
                        'IsHemodialysis' => $patients->patient_registry_details->isHemodialysis,
                        'DOTS' => $patients->patient_registry_details->isTBDots,
                        'PAD' => $patients->patient_registry_details->isPAD,
                        'HosPlan' => $hospitalPlan,
                        'PackageID' => '',
                        'CashBasis' => '',
                        'CreditLine' => $patients->patient_registry_details->guarantor_credit_Limit,
                        'PatientType' => '',
                        'OPDStatus' => '',
                        'IndustrialPatient' => $isIndustrialPatient,
                        'HMOApprovalNum' => $patients->patient_registry_details->guarantor_approval_no,
                        'LOANum' => $patients->patient_registry_details->guarantor_approval_code,
                        'Host_Name' => $patients->patient_registry_details->registry_hostname,
                        'UserID' => $patients->patient_registry_details->registry_userid,
                    ]);
                }

                Log::info('Item updated: ' .$patients);
                DB::connection('sqlsrv_medsys_patient_data')->commit();
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv_medsys_patient_data')->rollback();
            return response()->json(["message" => $e->getMessage()], 200);
        }

    }

    /**
     * Handle the Patients "updated" event.
     *
     * @param  \App\Models\HIS\Patients  $patients
     * @return void
     */
    public function updated(PatientMaster $patients)
    {
        //
    }

    /**
     * Handle the Patients "deleted" event.
     *
     * @param  \App\Models\HIS\Patients  $patients
     * @return void
     */
    public function deleted(PatientMaster $patients)
    {
        //
    }

    /**
     * Handle the Patients "restored" event.
     *
     * @param  \App\Models\HIS\Patients  $patients
     * @return void
     */
    public function restored(PatientMaster $patients)
    {
        //
    }

    /**
     * Handle the Patients "force deleted" event.
     *
     * @param  \App\Models\HIS\Patients  $patients
     * @return void
     */
    public function forceDeleted(PatientMaster $patients)
    {
        //
    }
}
