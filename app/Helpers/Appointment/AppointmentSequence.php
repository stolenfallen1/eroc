<?php

namespace App\Helpers\Appointment;

use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\MedsysSeriesNo;
use App\Models\HIS\services\Patient;

use App\Helpers\HIS\SysGlobalSetting;
use App\Models\Appointments\PatientAppointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentSequence 
{   
    protected $check_allow_Medsys;
    //check the status in medsys
    public function __construct()
    {
        $this->check_allow_Medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    
    // this function will get the latest seq_no value where code = MPID in CDGCore Sequence Table for New Patient Id
    private function getCDGPatientIdSeqNo()
    {  
        SystemSequence::where('code','MPID')->increment('seq_no'); 
        //increament the seq_no value in sysCentralSequences tbl where code MPID
        $query = SystemSequence::where('code', 'MPID')->select('seq_no','recent_generated')->first();
        return $query->seq_no;
    }

    // this function will get the latest seq_no value where code = MOPD or MERN in CDGCore Sequence Table for New Case No
    private function getCDGPatientCaseSeqNo()
    {
        SystemSequence::where('code', 'MERN')->increment('seq_no');
        SystemSequence::where('code', 'MOPD')->increment('seq_no');
         //increament the seq_no value in sysCentralSequences tbl where code MPID and code MERN
        $query = SystemSequence::where('code','MOPD')->select('seq_no','recent_generated');
        return $query->seq_no;
    }

    
    // this function will get the latest seq_no value where code = APN in CDGCore Sequence Table for New Appointment Reference Number
    private function getCDGReferenceSeqNo()
    {
        SystemSequence::where('code','APN')->increment('seq_no');
        $query = SystemSequence::select('seq_no', 'digit')->where('code', "APN")->where('isactive', 1)->first();
     
        $referenceNo = str_pad($query->seq_no, $query->digit, "0", STR_PAD_LEFT);
        return $referenceNo;


      
     
    }

    // this function will get the value of HospNum field  tbl admLastNumber in patient Data db
    private function getMedsysPatientIdSeqNo()
    {
        DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
        $query = MedsysSeriesNo::select('HospNum')->first();
        return $query->HospNum;
    }

     // this function will get the value of OPDId field  tbl admLastNumber in patient Data db
    private function getMedsyPatientCaseIdSeqNo()
    {
        DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
        $query = MedsysSeriesNo::select('OPDId')->first();
        return $query->OPDId;
    }

    //this function will process to give Patient Id and process the update the seq_no based on the value
    public function getPatientIdOnly()
    {
        if($this->check_allow_Medsys){ 
            $patient_Id = $this->getMedsysPatientIdSeqNo();}
        else{
            $patient_Id = $this->getCDGPatientIdSeqNo();
        }
        SystemSequence::where('code', 'MPID')->update(['seq_no'  => $patient_Id, 'recent_generated'   => $patient_Id]);
        return [
            'patient_Id'         => $patient_Id,
        ];
    }

    public function getCaseNoOnly()
    {
        if($this->check_allow_Medsys){
            $case_No = $this->getMedsyPatientCaseIdSeqNo();
        }
        else{
            $case_No = $this->getCDGPatientCaseSeqNo();
        }
        SystemSequence::where('code', 'MERN')->update(['seq_no'  => $case_No, 'recent_generated'   => $case_No]);
        SystemSequence::where('code', 'MOPD')->update(['seq_no'  => $case_No, 'recent_generated'   => $case_No]);
        return [
            'case_No'=> $case_No,
        ];
    }
    
     public function getBothSequence()
     {
        if($this->check_allow_Medsys){
            $patient_Id = $this->getMedsysPatientIdSeqNo();
            $case_No = $this->getMedsyPatientCaseIdSeqNo();
        }
        else{
            $patient_Id = $this->getCDGPatientIdSeqNo();
            $case_No = $this->getCDGPatientCaseSeqNo();
        }
        SystemSequence::where('code', 'MPID')->update(['seq_no'  => $patient_Id, 'recent_generated'   => $patient_Id]);
        SystemSequence::where('code', 'MOPD')->update(['seq_no'  => $case_No, 'recent_generated'   => $case_No]);
        SystemSequence::where('code', 'MERN')->update(['seq_no'  => $case_No, 'recent_generated'   => $case_No]);
        return [
            'patient_Id' => $patient_Id,
            'case_No'  => $case_No,
        ];
     }
     public function getAppointmentReference()
     {
        $appointment_ReferenceNumber = $this->getCDGReferenceSeqNo();
        $recent_ReferenceNumber = $appointment_ReferenceNumber - 1;
        SystemSequence::where('code', 'APN')->update(['seq_no'=> $appointment_ReferenceNumber, 'recent_generated' => $recent_ReferenceNumber]);
        return  $appointment_ReferenceNumber;
        
     }
    public function getSlots($payload)
     {
         $startDate = Carbon::now()->startOfDay();
         $selectedDate = $payload['appointment_Date'] ?? $startDate->format('Y-m-d');
         $slotNo = PatientAppointment::where('appointment_Date', '>=', $selectedDate)->whereIn('status_Id', [0,1, 2, 3])->count();
         return $slotNo + 1;
     }

}
