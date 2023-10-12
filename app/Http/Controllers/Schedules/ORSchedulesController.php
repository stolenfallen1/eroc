<?php

namespace App\Http\Controllers\Schedules;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\Scheduling\SeriesNo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedules\ORRoomsModel;
use App\Models\Schedules\ORNursesModel;
use Illuminate\Support\Facades\Storage;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\Status;
use App\Models\Schedules\ORCaseTypeModel;
use App\Models\Schedules\ORResidentModel;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\Schedules\ORScrubNursesModel;
use App\Models\Schedules\ORRoomTimeSlotModel;
use App\Helpers\Scheduling\OperatingRoomSchedule;
use App\Models\Schedules\ORCirculatingNursesModel;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;
use App\Models\Schedules\OperatingRoomScheduleStatusChangeLog;

class ORSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ORScheduledQueue()
    {
        $data =  (new OperatingRoomSchedule())->QueueScheduled();
        return response()->json($data, 200);
    }
    public function OROPTHAScheduledQueue()
    {
        $data =  (new OperatingRoomSchedule())->QueueScheduledOptha();
        return response()->json($data, 200);
    }

    public function confirmedchedules()
    {
        // list of patient for reception tab

        $data =  (new OperatingRoomSchedule())->confirmed_scheduled();
        return response()->json($data, 200);
    }

    public function pendingschedules()
    {
        // list of patient for reception tab
        $data =  (new OperatingRoomSchedule())->pending_scheduled();
        return response()->json($data, 200);
    }

    public function OperatingroomSchedulesStatus()
    {
        // list of patient for reception tab
        $data =  (new OperatingRoomSchedule())->operatingroom_status();
        return response()->json($data, 200);
    }

    public function getdoctor()
    {
        $data['Surgeons'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        $data['Anethesia'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getResident()
    {
        $data = ORCirculatingNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getORCirculatingNurses()
    {
        $data = ORCirculatingNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }
    public function getORScrubNurses()
    {
        $data = ORScrubNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }
    public function getORCategory()
    {
        $data = OperatingRoomCategory::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function getORRooms()
    {
        $data = ORRoomsModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function getORCaseTypes()
    {
        $data = ORCaseTypeModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }
    public function getORStatus()
    {
        
        $status = ['14','15','16','17','18','19','20','21','22','23','24','25','26'];
        if(Auth()->user()->role['name'] == 'Scrub Nurse'){
            $status = ['20','21','22','23','24','25','26'];
        }else if(Auth()->user()->role['name'] == 'Reception') {
            $status = ['14','15','16','17','18','19','20'];
        }

        $data = Status::where('isActive', '1')->whereIn('id', $status)->get();
        return response()->json($data, 200);
    }


    public function getORRoomTimeSlot()
    {
        $data = ORRoomTimeSlotModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function checkRoomAvailability()
    {
        $ORRoomTimSlotTransactionModel = ORRoomTimSlotTransactionModel::where('room_id', Request()->room_id);

        if (Request()->or_date) {
            $ORRoomTimSlotTransactionModel->where('timeslot_date', Request()->or_date);
        }
        $data = $ORRoomTimSlotTransactionModel->get();
        return response()->json($data, 200);
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try {

            $patientid =  $request->payload['patientid_reg'] ?? '';
            $caseno_reg =  $request->payload['caseno_reg'] ?? '';

            $id =  $request->payload['id'] ?? '';
            $scheduleddate =  $request->payload['scheduleddate'] ?? '';
            if(ORSchedulesModel::where('patient_id', $patientid)->where('case_id', $caseno_reg)->exists()) {
                $schedules = ORSchedulesModel::where('patient_id', $patientid)->where('case_id', $caseno_reg)->first();
                $schedules->where('id', $id)->update([
                    'room_id' => $request->payload['room_id'] ?? '',
                    'or_room_id' => $request->payload['or_room_id'] ?? '',
                    'station_id' => $request->payload['station_id'] ?? '',
                    'createdby' => Auth::user()->idnumber,
                    'schedule_date' => $scheduleddate,
                    'sex' => $request->payload['sexes'] ?? '',
                    'birthdate' => $request->payload['birthdate'] ?? '',
                    'age' => $request->payload['age'] ?? '',
                    'case_type_id' => $request->payload['case_type_id'] ?? '',
                    'category_id' => $request->payload['ORCategory'] ?? '',
                    'procedure_name' => $request->payload['procedurename'] ?? '',
                    'updatedby' => Auth::user()->idnumber,
                ]);
                $schedules->scheduleSurgeons()->where('id', $request->payload['schedule_surgeons']['id'])->where('schedule_id', $id)->update(
                    [
                    'branch_id' => Auth::user()->branch_id,
                    'doctor_id' => $request->payload['surgeon']['id'] ?? $request->payload['schedule_surgeons']['doctor_id'] ,
                    'lastname' => $request->payload['surgeon']['lastname'] ?? $request->payload['schedule_surgeons']['lastname'] ,
                    'firstname' => $request->payload['surgeon']['firstname'] ?? $request->payload['schedule_surgeons']['firstname'] ,
                    'middlename' => $request->payload['surgeon']['middlename'] ?? $request->payload['schedule_surgeons']['middlename'] ,
                    'updatedby' => Auth::user()->idnumber,
                ]
                );

                $schedules->scheduleAnesthesia()->where('id', Request()->payload['schedule_anesthesia']['id'])->where('schedule_id', $id)->update([
                    'branch_id' => Auth::user()->branch_id,
                    'doctor_id' => $request->payload['anesthesia']['id'] ?? $request->payload['schedule_anesthesia']['doctor_id'],
                    'lastname' => $request->payload['anesthesia']['lastname'] ?? $request->payload['schedule_anesthesia']['lastname'],
                    'firstname' => $request->payload['anesthesia']['firstname'] ?? $request->payload['schedule_anesthesia']['firstname'],
                    'middlename' => $request->payload['anesthesia']['middlename'] ?? $request->payload['schedule_anesthesia']['middlename'],
                    'updatedby' => Auth::user()->idnumber,
                ]);

                $schedules->scheduledRoomSlot()->where('id', Request()->payload['scheduled_room_slot']['id'])->where('schedule_id', $id)->update([
                    'timeslot_date' => $request->payload['scheduleddate'] ?? '',
                    'timeslot_id' => $request->payload['radioScheduledTime'] ?? '',
                    'room_id' => $request->payload['or_room_id'] ?? '',
                ]);

                $schedules->scheduledResident()->where('id', Request()->payload['scheduled_resident']['id'])->where('schedule_id', $id)->update([
                    'doctor_name' => $request->payload['ORResident']['circulatingnurses'] ?? $request->payload['scheduled_resident']['doctor_name'],
                    'updatedby' => Auth::user()->idnumber,
                ]);

                $schedules->scheduledCirculatingNurses()->where('specialty_id', 1)->where('id', Request()->payload['scheduled_circulating_nurses']['id'])->update([
                    'branch_id' => Auth::user()->branch_id,
                    'empnum' => $request->payload['ORCirculatingNurses']['empnum'] ??  $request->payload['scheduled_circulating_nurses']['empnum'] ,
                    'firstname' => $request->payload['ORCirculatingNurses']['firstname'] ??  $request->payload['scheduled_circulating_nurses']['firstname'] ,
                    'lastname' => $request->payload['ORCirculatingNurses']['lastname'] ?? $request->payload['scheduled_circulating_nurses']['lastname'] ,
                    'middlename' => $request->payload['ORCirculatingNurses']['middlename'] ?? $request->payload['scheduled_circulating_nurses']['middlename'] ,
                    'specialty_id' => 1,
                    'updatedby' => Auth::user()->idnumber,
                ]);
                $schedules->scheduledScrubNurses()->where('operating_room_scheduled_id', $id)->where('specialty_id', 2)->delete();
           
                if(isset($request->payload['SNurse']) && count($request->payload['SNurse']) > 0) {
                    foreach($request->payload['SNurse'] as $row) {
                        $schedules->scheduledScrubNurses()->create(
                            [
                            'empnum' =>  $row['empnum'] ?? '',
                            'branch_id' => Auth::user()->branch_id,
                            'firstname' => $row['firstname'] ?? '',
                            'lastname' => $row['lastname'] ?? '',
                            'middlename' => $row['middlename'] ?? '',
                            'specialty_id' => 2,
                            'createdby' => Auth::user()->idnumber,
                            'updatedby' => Auth::user()->idnumber,
                        ]
                        );
                    }
                }

            } else {

                $or_sequenceno = (new SeriesNo())->get_sequence('OR');
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);

                $schedule = ORSchedulesModel::create([
                    'orcase_no' => $generat_or_series,
                    'case_id' => $request->payload['caseno_reg'] ?? '',
                    'patient_id' => $patientid,
                    'room_id' => $request->payload['room_id'] ?? '',
                    'or_room_id' => $request->payload['or_room_id'] ?? '',
                    'station_id' => $request->payload['station_id'] ?? '',
                    'createdby' => Auth::user()->idnumber,
                    'schedule_date' => $scheduleddate,
                    'sex' => $request->payload['sexes'] ?? '',
                    'birthdate' => $request->payload['birthdate'] ?? '',
                    'age' => $request->payload['age'] ?? '',
                    'case_type_id' => $request->payload['case_type_id'] ?? '',
                    'category_id' => $request->payload['ORCategory'] ?? '',
                    'procedure_name' => $request->payload['procedurename'] ?? '',
                ]);
                $schedule->scheduleSurgeons()->create([
                    'branch_id' => Auth::user()->branch_id,
                    'doctor_id' => $request->payload['surgeon']['id'] ?? '',
                    'lastname' => $request->payload['surgeon']['lastname'] ?? '',
                    'firstname' => $request->payload['surgeon']['firstname'] ?? '',
                    'middlename' => $request->payload['surgeon']['middlename'] ?? '',
                    'createdby' => Auth::user()->idnumber,
                ]);

                $schedule->scheduleAnesthesia()->create([
                    'branch_id' => Auth::user()->branch_id,
                    'doctor_id' => $request->payload['anesthesia']['id'] ?? '',
                    'lastname' => $request->payload['anesthesia']['lastname'] ?? '',
                    'firstname' => $request->payload['anesthesia']['firstname'] ?? '',
                    'middlename' => $request->payload['anesthesia']['middlename'] ?? '',
                    'createdby' => Auth::user()->idnumber,
                ]);

                $schedule->scheduledRoomSlot()->create([
                    'timeslot_date' => $request->payload['scheduleddate'] ?? '',
                    'timeslot_id' => $request->payload['radioScheduledTime'] ?? '',
                    'room_id' => $request->payload['or_room_id'] ?? '',
                ]);

                $schedule->scheduledResident()->create([
                    'doctor_id' => $request->payload['ORResident']['id'] ?? '',
                    'doctor_name' => $request->payload['ORResident']['circulatingnurses'] ?? '',
                    'createdby' => Auth::user()->idnumber,
                ]);

                $schedule->scheduledCirculatingNurses()->create([
                    'branch_id' => Auth::user()->branch_id,
                    'empnum' => $request->payload['ORCirculatingNurses']['empnum'] ?? '',
                    'firstname' => $request->payload['ORCirculatingNurses']['firstname'] ?? '',
                    'lastname' => $request->payload['ORCirculatingNurses']['lastname'] ?? '',
                    'middlename' => $request->payload['ORCirculatingNurses']['middlename'] ?? '',
                    'specialty_id' => 1,
                    'createdby' => Auth::user()->idnumber,
                ]);

                if(isset($request->payload['SNurse']) && count($request->payload['SNurse']) > 0) {
                    foreach($request->payload['SNurse'] as $row) {
                        $schedule->scheduledScrubNurses()->create([
                            'empnum' =>  $row['empnum'] ?? '',
                            'branch_id' => Auth::user()->branch_id,
                            'firstname' => $row['firstname'] ?? '',
                            'lastname' => $row['lastname'] ?? '',
                            'middlename' => $row['middlename'] ?? '',
                            'specialty_id' => 2,
                            'createdby' => Auth::user()->idnumber,
                        ]);
                    }
                }

                $seriesno = $or_sequenceno->seq_no + 1;
                $or_sequenceno->update([
                    'seq_no' => $seriesno,
                    'recent_generated' => $generat_or_series,
                ]);
                $id = $schedule->id;
            }

            DB::connection('sqlsrv_schedules')->commit();
            DB::connection('sqlsrv')->commit();
            $this->convertTOjson($id);
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

        }

    }

    public function convertTOjson($id,$status = null)
    {

        $schedule_details = ORSchedulesModel::with('patientdetails', 'scheduledRoomSlot', 'scheduledCategory', 'station_details', 'scheduledStatus', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses')
        ->whereDate('schedule_date', Carbon::now()->format('Y-m-d'))
        ->where('id', $id)->first();

        if($schedule_details['category_id'] == '4') {
            $filename = 'scheduling/OPTHA-' . date('Y-m-d') . '.json';
        } else {
            $filename = 'scheduling/OR-' . date('Y-m-d') . '.json';
        }

        $dataArray = [];

        // Check if the data.json file exists
        if (Storage::disk('public')->exists($filename)) {
            // Read existing data from data.json
            $existingData = Storage::disk('public')->get($filename);
            // Decode the JSON data into an array
            $dataArray = json_decode($existingData, true);
        }

        $found = false;
        foreach ($dataArray as &$item) {
            if ($item['id'] === $id) {
                // Update the details if the data already exists
                $item['schedule_status_id'] = $schedule_details['schedule_status_id'];
                $item['status'] = $schedule_details['scheduledStatus']['Status_description'];
                $item['schedule_date'] = $schedule_details['scheduledRoomSlot']['timeslot_date'];
                $item['time'] = $schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['timeslot'];
                $item['starttime'] = (int)$schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['start_time'];
                $item['room_name'] = $schedule_details['scheduledRoomSlot']['scheduleRoom']['room_name'];
                $item['procedure_name'] = $schedule_details['procedure_name'];
                $item['surgeons'] = $schedule_details['scheduleSurgeons']['surgeon_name'];
                $item['anesthesia'] = $schedule_details['scheduleAnesthesia']['anesthesia_name'];
                $item['resident'] = $schedule_details['scheduledResident']['doctor_name'];
                $item['schedule_status_id'] = $schedule_details['schedule_status_id'];
                $item['new_schedule_status_id'] = $status == null ? $schedule_details['schedule_status_id'] : $status;
                $item['status'] = $schedule_details['scheduledStatus']['Status_description'];
                $item['category'] = $schedule_details['scheduledCategory']['category_name'];
                $item['cirlating_nurse'] = $schedule_details['scheduledCirculatingNurses'];
                $item['scrub_nurse'] = $schedule_details['scheduledScrubNurses'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $newData = [
                'id' => $schedule_details['id'],
                'patient_id' => $schedule_details['patient_id'],
                'case_id' => $schedule_details['case_id'],
                'orcase_no' => $schedule_details['orcase_no'],
                'patientname' => $schedule_details['patientdetails']['patient_name'],
                'schedule_date' => $schedule_details['scheduledRoomSlot']['timeslot_date'],
                'time' => $schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['timeslot'],
                'starttime' => (int)$schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['start_time'],
                'room_name' => $schedule_details['scheduledRoomSlot']['scheduleRoom']['room_name'],
                'procedure_name' => $schedule_details['procedure_name'],
                'surgeons' => $schedule_details['scheduleSurgeons']['surgeon_name'],
                'anesthesia' => $schedule_details['scheduleAnesthesia']['anesthesia_name'],
                'resident' => $schedule_details['scheduledResident']['doctor_name'],
                'schedule_status_id' => $schedule_details['schedule_status_id'],
                'status' => $schedule_details['scheduledStatus']['Status_description'],
                'category' => $schedule_details['scheduledCategory']['category_name'],
                'cirlating_nurse' => $schedule_details['scheduledCirculatingNurses'],
                'scrub_nurse' => $schedule_details['scheduledScrubNurses'],
            ];
            $dataArray[] = $newData;
        }
        $updatedData = json_encode($dataArray, JSON_PRETTY_PRINT);

        // Save the updated JSON data back to data.json
        Storage::disk('public')->put($filename, $updatedData);

    }

    public function ProccedWaitingRoom(Request $request)
    {

        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = Request()->payload;

            // Update the ORSchedulesModel record
            ORSchedulesModel::where('id', $payload['id'])->update([
                'schedule_status_id' => $payload['status_id'],
                'isprocessed' => 1,
                'processedBy' => Auth::user()->idnumber,
                'processed_date' => Carbon::now(),
            ]);

            // Update or create the OperatingRoomScheduleStatusChangeLog record
            $checklogs = OperatingRoomScheduleStatusChangeLog::where('operating_room_scheduled_id', $payload['id'])
            ->where('previous_status_id', $payload['previous_status_id'])
            ->where('new_status_id', $payload['status_id'])->first();
            if($checklogs) {
                $checklogs->update([
                   'doctor_id' => $payload['doctor_id'],
                   'operating_room_scheduled_id' => $payload['id'],
                   'previous_status_id' => $payload['previous_status_id'],
                   'new_status_id' => $payload['status_id'],
                   'updatedby' => Auth::user()->idnumber,
                ]);
            } else {
                OperatingRoomScheduleStatusChangeLog::create([
                    'doctor_id' => $payload['doctor_id'],
                    'operating_room_scheduled_id' => $payload['id'],
                    'previous_status_id' => $payload['previous_status_id'],
                    'new_status_id' => $payload['status_id'],
                    'createdby' => Auth::user()->idnumber,
                ]);
            }

            DB::connection('sqlsrv_schedules')->commit();
            $this->convertTOjson($payload['id'], $payload['previous_status_id']);
            return response()->json(["message" =>  'Transfer to Waiting Room successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function show(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function edit(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }
}
