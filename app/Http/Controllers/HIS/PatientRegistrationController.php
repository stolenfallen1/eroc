<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\GetIP;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use App\Helpers\HIS\SeriesNo;

use App\Models\HIS\PatientMaster;
use App\Helpers\HIS\MedsysPatient;
use App\Models\HIS\MedsysSeriesNo;
use App\Models\HIS\MedsysGuarantor;
use App\Models\HIS\PatientRegistry;
use App\Helpers\HIS\Medsys_SeriesNo;
use App\Http\Controllers\Controller;
use App\Models\HIS\MedsysOutpatient;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\MedsysHemoPatient;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\MedsysPatientAllergies;
use App\Models\HIS\MedsysPatientInformant;
use App\Models\HIS\MedsysPatientOPDHistory;
use App\Models\HIS\MedsysDoctorConsultation;
use App\Models\HIS\MedsysPatientMasterDetails;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class PatientRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $department;
    protected $check_is_allow_medsys;

    public function __construct()
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->department = Auth()->user();
    }

    public function index()
    {
        try {
            if($this->check_is_allow_medsys) {
                $data = (new MedsysPatient())->medsys_patient_searchable();
            } else {
                $data = (new Patient())->patient_registry_searchable();
            }
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function search()
    {
        try {
            if($this->check_is_allow_medsys) {
                $data = (new MedsysPatient())->medsys_patient_master_searchable();
            } else {
                $data = (new Patient())->patient_master_searchable();
            }
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }



    public function check_patient_details()
    {
        try {

            $data['currently_register'] = (new Patient())->check_patient();
            $data['check_is_allow_medsys'] = $this->check_is_allow_medsys;
            if($this->check_is_allow_medsys) {
                $data['is_currently_register'] = (new MedsysPatient())->medsys_check_patient();
                $data['is_admitted'] = (new MedsysPatient())->medsys_is_confined();
                $data['patient_details'] = (new MedsysPatient())->medsys_patient_details();
            } else {
                $data['patient_details'] = (new Patient())->patient_details();
            }

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    
    public function store(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();

        try {
            $pid_sequence = (new SeriesNo())->get_sequence('PID');
            $opd_sequence = (new SeriesNo())->get_sequence('OPD');

            // medsys series hospital no
            $medsys_pid_sequence = (new SeriesNo())->get_sequence('MPID');
            $medsys_opd_sequence = (new SeriesNo())->get_sequence('MOPD');

            // new HIS series Admission no
            $generated_pid_no = (new SeriesNo())->generate_series($pid_sequence->seq_no, $pid_sequence->digit);
            $generated_opd_no = (new SeriesNo())->generate_series($opd_sequence->seq_no, $opd_sequence->digit);

            // new HIS series Admission no
            $generated_medsys_pid_no = (new SeriesNo())->generate_series($medsys_pid_sequence->seq_no, $medsys_pid_sequence->digit);
            $generated_medsys_opd_no = (new SeriesNo())->generate_series($medsys_opd_sequence->seq_no, $medsys_opd_sequence->digit);

            $previous_patient_id = $request->patient_master['previous_patient_id'] ?? '';
            $patient_id = $request->patient_master['patient_id'] ?? '';
            $mscAccount_type = $request->patient_registry['registry_info']['mscAccount_type'] ?? '';
            $patient_type = $request->patient_registry['registry_info']['patient_type'] ?? '';

            $guarantor_code = isset($request->patient_registry['guarantor_details']['guarantor']['guarantor_code']) ? $request->patient_registry['guarantor_details']['guarantor']['guarantor_code'] : '';
            $guarantor_name = isset($request->patient_registry['guarantor_details']['guarantor']['guarantor_name']) ? $request->patient_registry['guarantor_details']['guarantor']['guarantor_name'] : '';
            $attending_doctor = isset($request->patient_registry['attending_doctor']['doctor_code']) ?$request->patient_registry['attending_doctor']['doctor_code'] : $request->patient_registry['attending_doctor'];
            $doctor_name = isset($request->patient_registry['attending_doctor']['doctor_name']) ?$request->patient_registry['attending_doctor']['doctor_name'] : $request->patient_registry['attending_doctor_name'];

            $isprivate = 0;
            $isHemodialysis = 0;
            $isChemotherapy = 0;
            $isTBDots = 0;
            $isRadioTherapy = 0;
            
            switch($patient_type) {
                case '2':
                    $isHemodialysis = 1;
                    break;
                case '3':
                    $isChemotherapy = 1;
                    break;
                case '4':
                    $isTBDots = 1;
                    break;
                case '5':
                    $isRadioTherapy = 1;
                    break;
                default:
                    $isprivate = 0;
                    break;
            }

            if ($this->check_is_allow_medsys) {


                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
                
                // DB::connection('sqlsrv_medsys_patientdatacdg')->select("SET NOCOUNT ON ; EXEC sp_get_medsys_sequence 'hospnum'");
                // DB::connection('sqlsrv_medsys_patientdatacdg')->select("SET NOCOUNT ON ; EXEC sp_get_medsys_sequence 'opdid'");

                $checkpatientMaster = PatientMaster::select('previous_patient_id')->where('previous_patient_id', $previous_patient_id)->exists();
                $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'OPDId')->first();
                if (!$patient_id) {
                    $generated_medsys_patient_id_no = $check_medsys_series_no->HospNum;
                } else {
                    $generated_medsys_patient_id_no = $previous_patient_id;
                }
                $generated_medsys_hospital_opd_no = $check_medsys_series_no->OPDId.'B';
            } else {
                $checkpatientMaster = PatientMaster::select('patient_id')->where('patient_id', $patient_id)->exists();
                if($checkpatientMaster){
                    $generated_medsys_patient_id_no = $patient_id;
                }else{
                    $generated_medsys_patient_id_no = $generated_pid_no;
                }
                $generated_medsys_hospital_opd_no = $generated_opd_no;
            }

            if ($mscAccount_type != '1') {
                $account = $guarantor_code;
            } else {
                $account = $generated_medsys_patient_id_no;
            }

            $patientMasterData = [
                'title_id' => $request->patient_master['title'] ?? '',
                'firstname' => $request->patient_master['firstname'] ?? '',
                'lastname' => $request->patient_master['lastname'] ?? '',
                'middlename' => $request->patient_master['middlename'] ?? '',
                'suffix_id' => isset($request->patient_master['suffix_id']) ? $request->patient_master['suffix_id'] : '',
                'birthdate' => $request->patient_master['birthdate'] ?? '',
                'age' => $request->patient_master['age'] ?? '',
                'birthplace' => $request->patient_master['birthplace'] ?? '',
                'nationality_id' => (int) ($request->patient_master['nationality_id']['id'] ?? $request->patient_master['nationality_id']),
                'country_id' => (int) ($request->patient_master['country_id'] ?? ''),
                'branch_id' => Auth()->user()->branch_id,
                'sex_id' => (int) ($request->patient_master['sex_id'] ?? ''),
                'civilstatus_id' => (int) ($request->patient_master['civilstatus_id']['id'] ?? $request->patient_master['civilstatus_id']),
                'religion_id' => (int) ($request->patient_master['religion_id']['id'] ?? $request->patient_master['religion_id']),
                'mobile_number' => $request->patient_master['mobile_number'] ?? '',
                'SeniorCitizen_ID_Number' => $request->patient_master['SeniorCitizen_ID_Number'] ?? '',
                'telephone_number' => $request->patient_master['telephone_number'] ?? '',
                'PWD_ID_Number' => $request->patient_master['PWD_ID_Number'] ?? '',
                'bldgstreet' => $request->patient_master['bldgstreet'] ?? '',
                'region_id' => $request->patient_master['address']['region_id'] ?? '',
                'province_id' => $request->patient_master['address']['province_id'] ?? '',
                'municipality_id' => $request->patient_master['address']['municipality_id'] ?? '',
                'barangay_id' => $request->patient_master['address']['barangay_id'] ?? '',
                'zipcode_id' => $request->patient_master['address']['zipcode_id'] ?? '',
                'email_address' => $request->patient_master['email_address'] ?? '',
                'createdBy' => Auth()->user()->idnumber,
            ];

            $patientRegistryData = [
                'mscBranches_id' => Auth()->user()->branch_id,
                'attending_doctor' => $attending_doctor,
                'attending_docotr_fullname' => $doctor_name,
                'isHemodialysis' => $isHemodialysis,
                'isTBDots' => $isTBDots,
                'isPAD' => '',
                'isRadioTherapy' => $isRadioTherapy,
                'isChemotherapy' => $isChemotherapy,
                'registry_hostname' => (new GetIP())->getHostname(),
                'mscPatient_category' => $request->patient_registry['registry_info']['mscPatient_category'] ?? '',
                'register_source' => $request->patient_registry['registry_info']['register_source'] ?? '',
                'register_source_case_no' => $request->patient_registry['registry_info']['register_source_case_no'] ?? '',
                'mscPrice_Schemes' => $request->patient_registry['registry_info']['mscPrice_Schemes'] ?? '',
                'mscPrice_Groups' => $request->patient_registry['registry_info']['mscPrice_Groups'] ?? '',
                'mscAccount_trans_types' => $request->patient_registry['registry_info']['mscAccount_trans_types'] ?? '',
                'register_type' => $request->patient_registry['registry_info']['register_type'] ?? '',
                'mscAccount_type' => $request->patient_registry['registry_info']['mscAccount_type'] ?? '',
                'guarantor_id' => $account,
                'guarantor_name' => $guarantor_name,
                'guarantor_approval_code' => $guarantor_code,
                'guarantor_approval_no' => isset($request->patient_registry['guarantor_details']['guarantor_approval_no']) ? $request->patient_registry['guarantor_details']['guarantor_approval_no'] : '',
                'guarantor_approval_date' => isset($request->patient_registry['guarantor_details']['guarantor_approval_date']) ? $request->patient_registry['guarantor_details']['guarantor_approval_date'] : null,
                'guarantor_validity_date' => isset($request->patient_registry['guarantor_details']['guarantor_validity_date']) ? $request->patient_registry['guarantor_details']['guarantor_validity_date'] : null,
                'guarantor_credit_Limit' => (float) (isset($request->patient_registry['guarantor_details']['guarantor_credit_Limit']) ? $request->patient_registry['guarantor_details']['guarantor_credit_Limit'] : 0),
                'guarantor_approval_remarks' => isset($request->patient_registry['guarantor_details']['guarantor_approval_remarks']) ? $request->patient_registry['guarantor_details']['guarantor_approval_remarks'] : '',
                'registry_remarks' => isset($request->patient_registry['guarantor_details']['registry_remarks']) ? $request->patient_registry['guarantor_details']['registry_remarks'] : '',
                'isWithCreditLimit' => isset($request->patient_registry['guarantor_details']['isWithCreditLimit']) ? $request->patient_registry['guarantor_details']['isWithCreditLimit'] : '',
                'registry_date' => Carbon::now(),
                'CreatedBy' => Auth()->user()->idnumber,
                'registry_userid' => Auth()->user()->idnumber,
            ];

            if (!$checkpatientMaster) {
                $patientMaster = PatientMaster::create(array_merge(['patient_id' => $generated_medsys_patient_id_no, 'previous_patient_id' => $generated_medsys_patient_id_no], $patientMasterData));
            } else {
                $patientMaster = PatientMaster::where('patient_id', $request->patient_master['patient_id'])->update(array_merge(['patient_id' => $generated_medsys_patient_id_no, 'previous_patient_id' => $generated_medsys_patient_id_no], $patientMasterData));
            }
            $check_registry = PatientRegistry::where('patient_id', $patient_id)->whereDate('registry_date', Carbon::now()->format('Y-m-d'))->first();
            if (!$check_registry) {
                PatientRegistry::create(array_merge(['patient_id' => $generated_medsys_patient_id_no, 'register_id_no' => $generated_opd_no, 'medsys_idnum' => $generated_medsys_hospital_opd_no], $patientRegistryData));
            } else {
                $check_registry->update($patientRegistryData);
            }

            if($pid_sequence->isSystem == '1') {
                $pid_sequence->update([
                    'seq_no' => (int)$pid_sequence->seq_no + 1,
                    'recent_generated' => $generated_pid_no,
                ]);
            }

            if($medsys_pid_sequence->isSystem == '1') {
                $medsys_pid_sequence->update([
                    'seq_no' => (int)$medsys_pid_sequence->seq_no + 1,
                    'recent_generated' => $generated_medsys_pid_no,
                ]);
            }

            if($opd_sequence->isSystem == '1') {
                $opd_sequence->update([
                    'seq_no' => (int)$opd_sequence->seq_no + 1,
                    'recent_generated' => $generated_opd_no,
                ]);
            }

            if($medsys_opd_sequence->isSystem == '1') {
                $medsys_opd_sequence->update([
                    'seq_no' => (int)$medsys_opd_sequence->seq_no + 1,
                    'recent_generated' => $generated_medsys_opd_no,
                ]);
            }



            DB::connection('sqlsrv')->commit();

            $patients = PatientMaster::where('previous_patient_id', $generated_medsys_patient_id_no)->firstOrFail();
            $patientRegistryDetails = $patients->patient_registry_details;
            // Determine account number
            $accountNum = $patientRegistryDetails->mscAccount_type == '1' ? $patients->patient_id : $patientRegistryDetails->guarantor_id;

            // Create or update MedsysPatientMaster
            $newMedsysPatientMaster = MedsysPatientMaster::updateOrCreate(
                ['HospNum' => $generated_medsys_patient_id_no],
                [
                    'HospNum' => $generated_medsys_patient_id_no,
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
                ]
            );

            $previouspatient_id = trim($generated_medsys_patient_id_no);
            // Use the trimmed value in the query
            $checkifexist = DB::connection('sqlsrv_medsys_patient_data')
                ->table('tbmaster2')
                ->select('HospNum')
                ->where('HospNum', $previouspatient_id)
                ->first();

            $patient_details = [
                    'HospNum' => $previouspatient_id,
                    'BirthPlace' => $patients->birthplace,
                    'NationalityID' => $patients->nationality->map_item_id,
                    'ReligionID' => $patients->religion->map_item_id,
            ];
            if(!$checkifexist) {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbmaster2')->insert($patient_details);
            } else {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbmaster2')->where('HospNum', $previouspatient_id)->update($patient_details);
            }


            // Determine hospital plan and industrial patient status
            $hospitalPlan = $patientRegistryDetails->mscAccount_type == '1' ? 'P' : 'C';
            $isIndustrialPatient = $patientRegistryDetails->mscAccount_trans_types == 5 ? 'Y' : '';

            // Create or update MedsysOPDRegistry
            $isMedsysOPDPatientExists = $newMedsysPatientMaster->opd_registry()->updateOrCreate(
                [
                    'IDNum' => $patientRegistryDetails->medsys_idnum,
                    'HospNum' => $generated_medsys_patient_id_no
                ],
                [
                    'HospNum' => $generated_medsys_patient_id_no,
                    'IDNum' => $patientRegistryDetails->medsys_idnum,
                    'AccountNum' => $accountNum,
                    'AdmDate' => $patientRegistryDetails->registry_date,
                    'Age' => $patients->age,
                    'SeniorCitizenID' => $patients->SeniorCitizen_ID_Number ?? null,
                    'SeniorCitizen' => $patients->isSeniorCitizen ?? 0,
                    'IsRadiotherapy' => $patientRegistryDetails->isRadioTherapy,
                    'IsChemo' => $patientRegistryDetails->isChemotherapy,
                    'IsHemodialysis' => $patientRegistryDetails->isHemodialysis,
                    'DOTS' => $patientRegistryDetails->isTBDots,
                    'PAD' => $patientRegistryDetails->isPAD,
                    'HosPlan' => $hospitalPlan,
                    'PackageID' => '',
                    'CashBasis' => '',
                    'CreditLine' => $patientRegistryDetails->guarantor_credit_Limit,
                    'PatientType' => '',
                    'OPDStatus' => $patientRegistryDetails->registry_status ?? '',
                    'IndustrialPatient' => $isIndustrialPatient,
                    'HMOApprovalNum' => $patientRegistryDetails->guarantor_approval_no,
                    'LOANum' => $patientRegistryDetails->guarantor_approval_code,
                    'Host_Name' => $patientRegistryDetails->registry_hostname,
                    'UserID' => $patientRegistryDetails->registry_userid,
                ]
            );

            // Create or update MedsysHemoPatient (if applicable)
            // if(Auth()->user()->warehouse->isHemodialysis == 1) {

            if ($patientRegistryDetails->isHemodialysis == 1) {

                $result = MedsysHemoPatient::selectRaw('ISNULL(MAX(CAST(HemoNum AS INT)), 0) as HEmoNum')->get();
                $HEmoNum = $result[0]->HEmoNum + 1;
                $isMedsysHemoPatientExists = $newMedsysPatientMaster->hemodialysis_registry()->whereNull('DcrDate')->whereDate('ADMDate', Carbon::now()->format('Y-m-d'))->updateOrCreate(
                    [
                        'IDNum' => $patientRegistryDetails->medsys_idnum,
                        'HospNum' => $generated_medsys_patient_id_no
                    ],
                    [
                        'HospNum' => $generated_medsys_patient_id_no,
                        'IDNum' => $patientRegistryDetails->medsys_idnum,
                        'HemoNum' => $result[0]->HEmoNum + 1,
                        'AccountNum' => $accountNum,
                        'OPDStatus' => $patientRegistryDetails->registry_status ?? 'C',
                        'ADMDate' => $patientRegistryDetails->registry_date,
                        'DoctorID' => $patientRegistryDetails->attending_doctor,
                        'PatientType' => $patientRegistryDetails->register_source == 4 ? 'O' : ($patientRegistryDetails->register_source == 6 ? 'O' : 'I') ,
                        'isHemoRegister' => $patientRegistryDetails->isHemodialysis == 1 ? 'Y' : '0',
                        'RevokeUsername' => $patientRegistryDetails->isRevoked == 1 ? Auth()->user()->name : '',
                        'RevokeDate' => $patientRegistryDetails->isRevoked == 1 ? $patientRegistryDetails->revoked_date : '',
                        'UserID' => $patientRegistryDetails->registry_userid,
                    ]
                );
            } else {

                $newMedsysPatientMaster->hemodialysis_registry()->whereNull('DcrDate')->where('IDNum', $patientRegistryDetails->medsys_idnum)->where('HospNum', $generated_medsys_patient_id_no)->whereDate('ADMDate', Carbon::now()->format('Y-m-d'))->update(
                    [
                    'HospNum' => $generated_medsys_patient_id_no,
                    'IDNum' => $patientRegistryDetails->medsys_idnum,
                    'AccountNum' => $accountNum,
                    'OPDStatus' => $patientRegistryDetails->registry_status ?? 'C',
                    'ADMDate' => $patientRegistryDetails->registry_date,
                    'DoctorID' => $patientRegistryDetails->attending_doctor,
                    'PatientType' => $patientRegistryDetails->register_source == 4 ? 'O' : ($patientRegistryDetails->register_source == 6 ? 'O' : 'I') ,
                    'isHemoRegister' => $patientRegistryDetails->isHemodialysis == 1 ? 'Y' : '0',
                    'RevokeUsername' => $patientRegistryDetails->isRevoked == 1 ? Auth()->user()->name : '',
                    'RevokeDate' => $patientRegistryDetails->isRevoked == 1 ? $patientRegistryDetails->revoked_date : '',
                    'UserID' => $patientRegistryDetails->registry_userid,
                    ]
                );

            }
            // }

            // Create or update MedsysPatientAllergies
            MedsysPatientAllergies::updateOrCreate(
                ['HospNum' => $generated_medsys_patient_id_no],
                ['HospNum' => $generated_medsys_patient_id_no]
            );

            // Create or update MedsysGuarantor
            MedsysGuarantor::updateOrCreate(
                ['IDNum' => $patientRegistryDetails->medsys_idnum],
                ['IDNum' => $patientRegistryDetails->medsys_idnum]
            );

            // Create or update MedsysPatientInformant
            MedsysPatientInformant::updateOrCreate(
                ['IDNum' => $patientRegistryDetails->medsys_idnum],
                ['IDNum' => $patientRegistryDetails->medsys_idnum]
            );

            // Create or update MedsysPatientOPDHistory
            MedsysPatientOPDHistory::updateOrCreate(
                ['IdNum' => $patientRegistryDetails->medsys_idnum],
                ['IdNum' => $patientRegistryDetails->medsys_idnum]
            );

            DB::connection('sqlsrv_patient_data')->commit();
            // $this->medsys_patient_registration($generated_medsys_patient_id_no);

            DB::connection('sqlsrv_medsys_patient_data')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollback();
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_medsys_patient_data')->rollback();
            return response()->json(["message" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {

        DB::beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();

        try {
            $previous_patient_id = $request->patient_master['previous_patient_id'] ?? '';
            $medsys_idnun = $request->patient_master['medsys_idnun'] ?? '';

            if ($this->check_is_allow_medsys) {
                $patientMaster = PatientMaster::where('previous_patient_id', $previous_patient_id)->firstOrFail();
                $registry = PatientRegistry::whereDate('registry_date', Carbon::now()->format('Y-m-d'))
                    ->where('patient_id', $patientMaster->patient_id)
                    ->firstOrFail();
                $id = $registry->id;
            } else {
                $patientMaster = PatientMaster::findOrFail($request->patient_master['id']);
            }
                        
            $mscAccount_type = $request->patient_registry['registry_info']['mscAccount_type'] ?? '';
            $patient_type = $request->patient_registry['registry_info']['patient_type'] ?? '';
            
         
            $guarantor_code = isset($request->patient_registry['guarantor_details']['guarantor']['guarantor_code']) ? $request->patient_registry['guarantor_details']['guarantor']['guarantor_code'] : $request->patient_registry['guarantor_details']['guarantor']['guarantor_code'];
            $guarantor_name = isset($request->patient_registry['guarantor_details']['guarantor']['guarantor_name']) ? $request->patient_registry['guarantor_details']['guarantor']['guarantor_name'] : $request->patient_registry['guarantor_details']['guarantor']['guarantor_name'];
            $attending_doctor = isset($request->patient_registry['attending_doctor']['doctor_code']) ? $request->patient_registry['attending_doctor']['doctor_code'] : $request->patient_registry['attending_doctor'];
            $doctor_name = isset($request->patient_registry['attending_doctor']['doctor_name']) ? $request->patient_registry['attending_doctor']['doctor_name'] : $request->patient_registry['attending_doctor_name'];


            $isprivate = 0;
            $isHemodialysis = 0;
            $isChemotherapy = 0;
            $isTBDots = 0;
            $isRadioTherapy = 0;
            switch($patient_type) {
                case '2':
                    $isHemodialysis = 1;
                    break;
                case '3':
                    $isChemotherapy = 1;
                    break;
                case '4':
                    $isTBDots = 1;
                    break;
                case '5':
                    $isRadioTherapy = 1;
                    break;
                default:
                    $isprivate = 0;
                    break;
            }

            $patientMaster->update([
                'patient_id' => $request->patient_master['patient_id'],
                'previous_patient_id' => $request->patient_master['previous_patient_id'],
                'title_id' => $request->patient_master['title'] ?? '',
                'firstname' => $request->patient_master['firstname'],
                'lastname' => $request->patient_master['lastname'],
                'middlename' => $request->patient_master['middlename'],
                'suffix_id' => (int)($request->patient_master['suffix_id'] ?? ''),
                'birthdate' => $request->patient_master['birthdate'],
                'age' => $request->patient_master['age'],
                'birthplace' => $request->patient_master['birthplace'],
                'nationality_id' => (int)(isset($request->patient_master['nationality_id']['id']) ? $request->patient_master['nationality_id']['id'] : $request->patient_master['nationality_id']),
                'country_id' => (int)($request->patient_master['country_id'] ?? ''),
                'branch_id' => Auth()->user()->branch_id,
                'sex_id' => (int)($request->patient_master['sex_id'] ?? ''),
                'civilstatus_id' => (int)(isset($request->patient_master['civilstatus_id']['id']) ? $request->patient_master['civilstatus_id']['id'] : $request->patient_master['civilstatus_id']),
                'religion_id' => (int)(isset($request->patient_master['religion_id']['id']) ? $request->patient_master['religion_id']['id'] : $request->patient_master['religion_id']),
                'mobile_number' => $request->patient_master['mobile_number'] ?? '',
                'SeniorCitizen_ID_Number' => $request->patient_master['SeniorCitizen_ID_Number'] ?? '',
                'telephone_number' => $request->patient_master['telephone_number'] ?? '',
                'PWD_ID_Number' => $request->patient_master['PWD_ID_Number'] ?? '',
                'bldgstreet' => $request->patient_master['bldgstreet'] ?? '',
                'region_id' => $request->patient_master['region_id'] ?? '',
                'province_id' => $request->patient_master['province_id'] ?? '',
                'municipality_id' => $request->patient_master['municipality_id'] ?? '',
                'barangay_id' => $request->patient_master['barangay_id'] ?? '',
                'zipcode_id' => $request->patient_master['zipcode_id'] ?? '',
                'email_address' => $request->patient_master['email_address'] ?? '',
                'UpdatedBy' => Auth()->user()->idnumber,
            ]);

            $account = '';
            $guarator = '';
            $guarantor_approval_date = null;
            $guarantor_validity_date = null;
            if ($mscAccount_type != 1) {
                $account = $guarantor_code;
                $guarator = $guarantor_name;
            } else {
                $account = $request->patient_master['patient_id'];
                $guarator = '';
            }

            if (isset($request->patient_registry['guarantor_details']['guarantor_approval_date'])) {
                $guarantor_approval_date = $request->patient_registry['guarantor_details']['guarantor_approval_date'];
                $guarantor_validity_date = $request->patient_registry['guarantor_details']['guarantor_validity_date'];
            }

            $patientMaster->patient_registry()->where('id', $id)->update([
                'medsys_idnum' => $request->patient_registry['register_id_no'] ?? '',
                'mscBranches_id' => Auth()->user()->branch_id,
                'attending_doctor' =>  $attending_doctor,
                'attending_docotr_fullname' => $doctor_name,
                'isHemodialysis' => $isHemodialysis,
                'isTBDots' => $isTBDots,
                'isPAD' => '',
                'isRadioTherapy' => $isRadioTherapy,
                'isChemotherapy' => $isChemotherapy,
                'registry_hostname' => (new GetIP())->getHostname(),
                'mscPatient_category' => $request->patient_registry['registry_info']['mscPatient_category'] ?? '',
                'register_source' => $request->patient_registry['registry_info']['register_source'] ?? '',
                'register_source_case_no' => $request->patient_registry['registry_info']['register_source_case_no'] ?? '',
                'mscPrice_Schemes' => $request->patient_registry['registry_info']['mscPrice_Schemes'] ?? '',
                'mscPrice_Groups' => $request->patient_registry['registry_info']['mscPrice_Groups'] ?? '',
                'mscAccount_trans_types' => $request->patient_registry['registry_info']['mscAccount_trans_types'] ?? '',
                'register_type' => $request->patient_registry['registry_info']['register_type'] ?? '',
                'mscAccount_type' => $mscAccount_type ?? '',
                'guarantor_id' => $account,
                'guarantor_name' => $guarator,
                'guarantor_approval_code' => $request->patient_registry['guarantor_details']['guarantor_approval_code'] ?? '',
                'guarantor_approval_no' => $request->patient_registry['guarantor_details']['guarantor_approval_no'] ?? '',
                'guarantor_approval_date' => $guarantor_approval_date != 'Invalid date' ? $guarantor_approval_date : null,
                'guarantor_validity_date' => $guarantor_validity_date != 'Invalid date' ? $guarantor_validity_date : null,
                'guarantor_credit_Limit' => (float)$request->patient_registry['guarantor_details']['guarantor_credit_Limit'] ?? 0,
                'guarantor_approval_remarks' => $request->patient_registry['guarantor_details']['guarantor_approval_remarks'] ?? '',
                'registry_remarks' => $request->patient_registry['registry_remarks'] ?? '',
                'isWithCreditLimit' => $request->patient_registry['guarantor_details']['isWithCreditLimit'] ?? '',
                'registry_status' => $request->patient_registry['registry_status'] == true ? 'R' : '',
                'isRevoked' => $request->patient_registry['registry_status'] ?? 0,
                'revoked_remarks' => $request->patient_registry['registry_status'] == true ? $request->patient_registry['revoked_remarks'] : '',
                'revoked_date' => $request->patient_registry['registry_status'] == true ? Carbon::now() : null,
                'revoked_hostname' => $request->patient_registry['registry_status'] == true ? (new GetIP())->getHostname() : '',
                'revokedBy' => $request->patient_registry['registry_status'] == true ? Auth()->user()->idnumber : '',
                'registry_date' => Carbon::now(),
                'UpdatedBy' => Auth()->user()->idnumber,
                'registry_remarks' => $request->patient_registry['registry_remarks'] ?? '',
            ]);

            DB::commit();
            $this->medsys_patient_registration($previous_patient_id);

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_patient_data')->commit();

            return response()->json(["message" => 'Record successfully saved', 'status' => 200], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            \Log::error("An error occurred: " . $e->getMessage());
            return response()->json(["message" => $e->getMessage()], 200);
        }

    }

    public function medsys_patient_registration($previous_patient_id)
    {
        if ($this->check_is_allow_medsys) {
            // DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();

            // try {
            // Retrieve patient data

            // Retrieve patient data
            $patients = PatientMaster::where('previous_patient_id', $previous_patient_id)->firstOrFail();
            $patientRegistryDetails = $patients->patient_registry_details;

            // Determine account number
            $accountNum = $patientRegistryDetails->mscAccount_type == '1' ? $patients->patient_id : $patientRegistryDetails->guarantor_id;

            // Create or update MedsysPatientMaster
            $newMedsysPatientMaster = MedsysPatientMaster::updateOrCreate(
                ['HospNum' => $previous_patient_id],
                [
                    'HospNum' => $previous_patient_id,
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
                ]
            );


            $previouspatient_id = trim($previous_patient_id);

            // Use the trimmed value in the query
            $checkifexist = DB::connection('sqlsrv_medsys_patient_data')
                ->table('tbmaster2')
                ->select('HospNum')
                ->where('HospNum', $previouspatient_id)
                ->first();

            $patient_details = [
                    'HospNum' => $previouspatient_id,
                    'BirthPlace' => $patients->birthplace,
                    'NationalityID' => $patients->nationality->map_item_id,
                    'ReligionID' => $patients->religion->map_item_id,
            ];
            if(!$checkifexist) {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbmaster2')->insert($patient_details);
            } else {
                DB::connection('sqlsrv_medsys_patient_data')->table('tbmaster2')->where('HospNum', $previouspatient_id)->update($patient_details);
            }


            // Determine hospital plan and industrial patient status
            $hospitalPlan = $patientRegistryDetails->mscAccount_type == '1' ? 'P' : 'C';
            $isIndustrialPatient = $patientRegistryDetails->mscAccount_trans_types == 5 ? 'Y' : '';

            // Create or update MedsysOPDRegistry
            $isMedsysOPDPatientExists = $newMedsysPatientMaster->opd_registry()
            ->whereDate('AdmDate', Carbon::now()->format('Y-m-d'))
            ->where('IDNum',  $patientRegistryDetails->medsys_idnum)
            ->where('HospNum',$previous_patient_id)
            ->update(
                [
                    'HospNum' => $previous_patient_id,
                    'IDNum' => $patientRegistryDetails->medsys_idnum,
                    'AccountNum' => $accountNum,
                    'AdmDate' => $patientRegistryDetails->registry_date,
                    'Age' => $patients->age,
                    'SeniorCitizenID' => $patients->SeniorCitizen_ID_Number ?? null,
                    'SeniorCitizen' => $patients->isSeniorCitizen ?? 0,
                    'IsRadiotherapy' => $patientRegistryDetails->isRadioTherapy,
                    'IsChemo' => $patientRegistryDetails->isChemotherapy,
                    'IsHemodialysis' => $patientRegistryDetails->isHemodialysis,
                    'DOTS' => $patientRegistryDetails->isTBDots,
                    'PAD' => $patientRegistryDetails->isPAD,
                    'HosPlan' => $hospitalPlan,
                    'PackageID' => '',
                    'CashBasis' => '',
                    'CreditLine' => $patientRegistryDetails->guarantor_credit_Limit,
                    'PatientType' => '',
                    'OPDStatus' => $patientRegistryDetails->registry_status ?? '',
                    'IndustrialPatient' => $isIndustrialPatient,
                    'HMOApprovalNum' => $patientRegistryDetails->guarantor_approval_no,
                    'LOANum' => $patientRegistryDetails->guarantor_approval_code,
                    'Host_Name' => $patientRegistryDetails->registry_hostname,
                    'UserID' => $patientRegistryDetails->registry_userid,
                ]
            );

            // Create or update MedsysHemoPatient (if applicable)
            // if(Auth()->user()->warehouse->isHemodialysis == 1) {

                if ($patientRegistryDetails->isHemodialysis == 1) {

                    $result = MedsysHemoPatient::selectRaw('ISNULL(MAX(CAST(HemoNum AS INT)), 0) as HEmoNum')->get();
                    $HEmoNum = $result[0]->HEmoNum + 1;
                    $isMedsysHemoPatientExists = $newMedsysPatientMaster->hemodialysis_registry()
                    ->whereNull('DcrDate')
                    ->whereDate('ADMDate', Carbon::now()->format('Y-m-d'))
                    ->where('IDNum',  $patientRegistryDetails->medsys_idnum)
                    ->where('HospNum',$previous_patient_id)
                    ->update(
                        [
                            'HospNum' => $previous_patient_id,
                            'IDNum' => $patientRegistryDetails->medsys_idnum,
                            'HemoNum' => $result[0]->HEmoNum + 1,
                            'AccountNum' => $accountNum,
                            'OPDStatus' => $patientRegistryDetails->registry_status ?? 'C',
                            'ADMDate' => $patientRegistryDetails->registry_date,
                            'DoctorID' => $patientRegistryDetails->attending_doctor,
                            'PatientType' => $patientRegistryDetails->register_source == 4 ? 'O' : ($patientRegistryDetails->register_source == 6 ? 'O' : 'I') ,
                            'isHemoRegister' => $patientRegistryDetails->isHemodialysis == 1 ? 'Y' : '0',
                            'RevokeUsername' => $patientRegistryDetails->isRevoked == 1 ? Auth()->user()->name : '',
                            'RevokeDate' => $patientRegistryDetails->isRevoked == 1 ? $patientRegistryDetails->revoked_date : '',
                            'UserID' => $patientRegistryDetails->registry_userid,
                        ]
                    );
                } else {

                    $newMedsysPatientMaster->hemodialysis_registry()->whereNull('DcrDate')
                    ->where('IDNum', $patientRegistryDetails->medsys_idnum)
                    ->where('HospNum', $previous_patient_id)
                    ->whereDate('ADMDate', Carbon::now()->format('Y-m-d'))
                    ->update(
                        [
                        'HospNum' => $previous_patient_id,
                        'IDNum' => $patientRegistryDetails->medsys_idnum,
                        'AccountNum' => $accountNum,
                        'OPDStatus' => $patientRegistryDetails->registry_status ?? 'C',
                        'ADMDate' => $patientRegistryDetails->registry_date,
                        'DoctorID' => $patientRegistryDetails->attending_doctor,
                        'PatientType' => $patientRegistryDetails->register_source == 4 ? 'O' : ($patientRegistryDetails->register_source == 6 ? 'O' : 'I') ,
                        'isHemoRegister' => $patientRegistryDetails->isHemodialysis == 1 ? 'Y' : '0',
                        'RevokeUsername' => $patientRegistryDetails->isRevoked == 1 ? Auth()->user()->name : '',
                        'RevokeDate' => $patientRegistryDetails->isRevoked == 1 ? $patientRegistryDetails->revoked_date : '',
                        'UserID' => $patientRegistryDetails->registry_userid,
                        ]
                    );

                }
            // }

            // Create or update MedsysPatientAllergies
            MedsysPatientAllergies::updateOrCreate(
                ['HospNum' => $previous_patient_id],
                ['HospNum' => $previous_patient_id]
            );

            // Create or update MedsysGuarantor
            MedsysGuarantor::updateOrCreate(
                ['IDNum' => $patientRegistryDetails->medsys_idnum],
                ['IDNum' => $patientRegistryDetails->medsys_idnum]
            );

            // Create or update MedsysPatientInformant
            MedsysPatientInformant::updateOrCreate(
                ['IDNum' => $patientRegistryDetails->medsys_idnum],
                ['IDNum' => $patientRegistryDetails->medsys_idnum]
            );

            // Create or update MedsysPatientOPDHistory
            MedsysPatientOPDHistory::updateOrCreate(
                ['IdNum' => $patientRegistryDetails->medsys_idnum],
                ['IdNum' => $patientRegistryDetails->medsys_idnum]
            );

            // DB::connection('sqlsrv_medsys_patient_data')->commit();
            // } catch (\Exception $e) {
            //     DB::connection('sqlsrv_medsys_patient_data')->rollback();
            //     return response()->json(["message" => $e->getMessage()], 200);
            // }
        }
    }

    public function destroy($id)
    {
        //
    }
}
