<?php

namespace App\Observers;

use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\HIS\MedsysERMaster;
use App\Models\HIS\MedsysOutpatient;
class HISPatientRegistryObserver
{
    /**
     * Handle the PatientRegistry "created" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    protected $check_is_allow_medsys;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function created(PatientRegistry $patientRegistry)
    {
        // try{
            $today = Carbon::now()->format('Y-m-d');
            $IDnum = $patientRegistry->case_No . 'B';


            if($this->check_is_allow_medsys && $patientRegistry) {

                if(intval($patientRegistry->mscAccount_Trans_Types) === 5) {
          
                    $ER_Patient = [
                        'Hospnum'           => $patientRegistry->patient_Id,
                        'IDnum'             => $patientRegistry->case_No . 'B',
                        'ERNum'             => $patientRegistry->er_Case_No         ?? '',
                        'AdmDate'           => $patientRegistry->registry_Date  
                                            ? $patientRegistry->registry_Date 
                                            : Carbon::now(),
                        'DoctorID1'         => $patientRegistry->attending_Doctor   ?? '',
                        'ReasonOfReferral'  => $patientRegistry->referral_Reason    ?? '',
                        'ReferredFrom'      => $patientRegistry->referred_From_HCI  ?? '',
                        'BEDNUMBER'         => $patientRegistry->er_Bedno           ?? '',
                        'ReferredTo'        => $patientRegistry->referred_To_HCI    ?? ''
                    ];

                    $OPD_Patient_Data = [
                        'HospNum'       =>  $patientRegistry->patient_Id,
                        'IDNum'         =>  $patientRegistry->case_No . 'B',
                        'ERNum'         =>  $patientRegistry->er_Case_No     ?? '',
                        'AdmDate'       =>  $patientRegistry->registry_Date 
                                        ?   $patientRegistry->registry_Date 
                                        :   Carbon::now(),
                        'DoctorID1'     =>  $patientRegistry->attending_Doctor   ?? '',
                        'AccountNum'    =>  $patientRegistry->guarantor_Id   ? $patientRegistry->guarantor_Id : $patientRegistry->patient_Id,
                        'UserID'        => $patientRegistry->createdBy
                    ];

                    $isRegisteredToday  = MedsysERMaster::where('IDnum', $IDnum)
                                        -> whereDate('AdmDate', $today)
                                        ->exists();

                    $isRegisterER   = MedsysERMaster::whereDate('AdmDate', $today)->updateOrcreate(['IDnum'   => $IDnum], $ER_Patient); 
                    $isRegisterOPD  = MedsysOutpatient::whereDate('AdmDate', $today)->updateOrCreate(['IDNum' => $IDnum], $OPD_Patient_Data);

                    $message        = $isRegisteredToday 
                                    ? 'Patient data updated successfully'
                                    : 'Patient data created successfully';


                    if($isRegisterER && $isRegisterOPD) {

                        Log::info($message);

                    } else {

                        Log::error('Failed to create or update patient data in Medsys.');
                    } 

                } elseif(intval($patientRegistry->mscAccount_Trans_Types) === 2) {

                    $OPD_Patient_Data = [
                        'HospNum'       =>  $patientRegistry->patient_Id,
                        'IDNum'         =>  $patientRegistry->case_No . 'B',
                        'ERNum'         =>  $patientRegistry->er_Case_No     ?? '',
                        'AdmDate'       =>  $patientRegistry->registry_Date 
                                        ?   $patientRegistry->registry_Date 
                                        :   Carbon::now(),
                        'DoctorID1'     =>  $patientRegistry->attending_Doctor   ?? '',
                        'AccountNum'    =>  $patientRegistry->guarantor_Id   ? $patientRegistry->guarantor_Id : $patientRegistry->patient_Id,
                        'UserID'        => $patientRegistry->createdBy
                    ];

                    $isRegisteredToday  = MedsysERMaster::where('IDnum', $IDnum)
                                        -> whereDate('AdmDate', $today)
                                        ->exists();

                    $isRegisterOPD  = MedsysOutpatient::whereDate('AdmDate', $today)->updateOrCreate(['IDNum' => $IDnum], $OPD_Patient_Data);

                    $message        = $isRegisteredToday 
                                    ? 'Patient data updated successfully'
                                    : 'Patient data created successfully';

                   if($isRegisterOPD) {

                        Log::info($message);

                    } else {

                        Log::error('Failed to create or update patient data in Medsys.');
                    } 
                } else {

                    //For InPatient
                }

            } else {

                Log::error('Permission denied or Patient Registry is invalid.');
            }
            
        // } catch(\Exception $e) {

        //     Log::error('Failed to process patient data in Medsys: ' . $e->getMessage());
        //     throw new \Exception('Failed to insert patient into Medsys: ' . $e->getMessage());

        // } 
    }

    /**
     * Handle the PatientRegistry "updated" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function updated(PatientRegistry $patientRegistry)
    {
    
        try {

            if($this->check_is_allow_medsys && $patientRegistry) {
                
                $today = Carbon::now()->format('Y-m-d');
                $erId = $patientRegistry->case_No . 'B';

                if(intval($patientRegistry->mscAccount_Trans_Types) === 5) {

                    $ER_Patient_Master  = MedsysERMaster::findOrFail($erId);
                    $MedsysOPD          = MedsysOutpatient::findOrFail($erId);
                    
                    if($ER_Patient_Master && $MedsysOPD) {

                        $ER_Patient_Master_Data = [
                            'Hospnum'           =>  $patientRegistry->patient_Id,
                            'ERNum'             =>  $patientRegistry->er_Case_No ?? $ER_Patient_Master->er_Case_No,
                            'AdmDate'           =>  $ER_Patient_Master->AdmDate  
                                                ?   $ER_Patient_Master->AdmDate 
                                                :   Carbon::now(),

                            'DoctorID1'         => $patientRegistry->attending_Doctor,
                            'ReasonOfReferral'  =>  $patientRegistry->referral_Reason,
                            'ReferredFrom'      =>  $patientRegistry->referred_From_HCI,
                            'BEDNUMBER'         =>  $patientRegistry->er_Bedno,
                            'ReferredTo'        =>  $patientRegistry->referred_To_HCI

                        ];

                        $OPD_Patient_Data = [
                            'HospNum'       =>  $patientRegistry->patient_Id,
                            'IDNum'         =>  $patientRegistry->case_No . 'B',
                            'ERNum'         =>  $patientRegistry->er_Case_No,
                            'AdmDate'       =>  $patientRegistry->registry_Date  
                                            ?   $patientRegistry->registry_Date 
                                            :   Carbon::now(),

                            'DoctorID1'     => $patientRegistry->attending_Doctor,                
                            'AccountNum'    =>  $patientRegistry->guarantor_Id   
                                            ?   $patientRegistry->guarantor_Id 
                                            :   $patientRegistry->patient_Id,
                            'UserID'        => $patientRegistry->updatedBy
                        ];

                        $isRegisteredToday  = MedsysERMaster::where('IDnum', $erId)
                                            -> whereDate('AdmDate', $today)
                                            ->exists();

                        $isUpdatedER    = MedsysERMaster::whereDate('AdmDate', $today)->updateOrCreate(['IDnum' => $erId], $ER_Patient_Master_Data);
                        $isUpdatedOPD   = MedsysOutpatient::whereDate('AdmDate', $today)->updateOrCreate(['IDNum' => $erId], $OPD_Patient_Data);

                        $message        = $isRegisteredToday 
                                        ? 'Patient data updated successfully'
                                        : 'Patient data created successfully';

                        if($isUpdatedER && $isUpdatedOPD) {

                            Log::info($message);

                        } else {

                            Log::info('Failed to Update data in Medsys ERMaster table..');
                        } 

                    } else {

                        Log::info('No patient Found');
                    }

                } elseif(intval($patientRegistry->mscAccount_Trans_Types) === 2) {

                    $MedsysOPD          = MedsysOutpatient::findOrFail($erId);

                    $OPD_Patient_Data = [
                        'HospNum'       =>  $patientRegistry->patient_Id,
                        'IDNum'         =>  $patientRegistry->case_No . 'B',
                        'ERNum'         =>  $patientRegistry->er_Case_No,
                        'AdmDate'       =>  $patientRegistry->registry_Date  
                                        ?   $patientRegistry->registry_Date 
                                        :   Carbon::now(),

                        'DoctorID1'     => $patientRegistry->attending_Doctor,                
                        'AccountNum'    =>  $patientRegistry->guarantor_Id   
                                        ?   $patientRegistry->guarantor_Id 
                                        :   $patientRegistry->patient_Id,
                    ];

                    $isRegisteredToday  = MedsysERMaster::where('IDnum', $erId)
                                            -> whereDate('AdmDate', $today)
                                            ->exists();

                    $isUpdatedOPD   = MedsysOutpatient::whereDate('AdmDate', $today)->updateOrCreate(['IDNum' => $erId], $OPD_Patient_Data);
                    
                    $message        = $isRegisteredToday 
                                        ? 'Patient data updated successfully'
                                        : 'Patient data created successfully';
                    if($isUpdatedOPD) {

                        Log::info($message);

                    } else {

                        Log::info('Failed to Update data in Medsys ERMaster table..');
                    } 

                } else {
                    //For InPatient
                }


            } else {

                Log::error('Cannot update is Either persion id denied or patient registry is empty');
            }
            
        } catch(\Exception $e) {

            Log::error('Failed to update patient info in  Medsys: ' . $e->getMessage());

            throw new \Exception('Failed to update patient into Medsys: ' . $e->getMessage());
        }
    }

    /**
     * Handle the PatientRegistry "deleted" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function deleted(PatientRegistry $patientRegistry)
    {
        //
    }

    /**
     * Handle the PatientRegistry "restored" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function restored(PatientRegistry $patientRegistry)
    {
        //
    }

    /**
     * Handle the PatientRegistry "force deleted" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function forceDeleted(PatientRegistry $patientRegistry)
    {
        //
    }
}
