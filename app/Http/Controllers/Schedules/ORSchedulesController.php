<?php

namespace App\Http\Controllers\Schedules;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HIS\MedsysSeriesNo;
use App\Models\Schedules\ORDoctor;
use App\Helpers\Scheduling\SeriesNo;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedules\ORRoomsModel;
use App\Models\Schedules\ORNursesModel;
use Illuminate\Support\Facades\Storage;
use App\Models\Schedules\ORPatientModel;
use App\Models\Schedules\ORRegistration;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\Status;
use App\Models\Schedules\ORCaseTypeModel;
use App\Models\Schedules\ORResidentModel;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\Schedules\ORScrubNursesModel;
use App\Models\Schedules\ORRoomTimeSlotModel;
use App\Models\Schedules\ORRegistrationTimeSlot;
use App\Models\Schedules\ORScheduleSurgeonModel;
use App\Helpers\Scheduling\OperatingRoomSchedule;
use App\Models\Schedules\OperatingRoomProcedures;
use App\Models\Schedules\ORCirculatingNursesModel;
use App\Models\Schedules\ORRegistrationProcedures;
use App\Models\Schedules\ORScheduleAnesthesiaModel;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;
use App\Models\Schedules\ORRegistrationPreferredSurgeon;
use App\Models\Schedules\ORRegistrationPreferredAnesthesia;
use App\Models\Schedules\OperatingRoomScheduleStatusChangeLog;

class ORSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getORSchedules()
    {
        $data =  (new OperatingRoomSchedule())->vwORSchedules();
        return response()->json($data, 200);
    }

    public function getORPatientDetails()
    {
        $data =  (new OperatingRoomSchedule())->vwORPatientDetails();
        return response()->json($data, 200);
    }

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
        $data['Surgeons'] = ORDoctor::where('isactive', '1')->where('specialty_id','1')->select('id', 'lastname', 'firstname', 'middlename')->orderBy('lastname', 'asc')->get();
        $data['Anethesia'] = ORDoctor::where('isactive', '1')->where('specialty_id','2')->select('id', 'lastname', 'firstname', 'middlename')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getResident()
    {
        $data = ORDoctor::where('isactive', '1')->where('specialty_id','3')->select('id', 'lastname', 'firstname', 'middlename')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getORProcedures()
    {
        $data = OperatingRoomProcedures::where('isactive', '1')->orderBy('descriptions', 'asc')->get();
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
        if(Auth()->user()->role['name'] == 'Scrub Nurse') {
            $status = ['20','21','22','23','24','25','26'];
        } elseif(Auth()->user()->role['name'] == 'Reception') {
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
        $ORRoomTimSlotTransactionModel = ORRoomTimSlotTransactionModel::where('room_id', Request()->room_id)->whereNotIn('timeslot_id', [0]);
        $ORScheduledModel = ORRoomTimSlotTransactionModel::where('room_id', Request()->room_id)->where('schedule_id', Request()->id)->whereNotIn('timeslot_id', [0]);
        if (Request()->or_date) {
            $ORRoomTimSlotTransactionModel->where('timeslot_date', Request()->or_date);
            $ORScheduledModel->where('timeslot_date', Request()->or_date);
        }
        $data['reserved_slot'] = $ORRoomTimSlotTransactionModel->get();
        $data['schedules'] = $ORScheduledModel->get();
        return response()->json($data, 200);
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
            if(!$id) {
                $or_sequenceno = (new SeriesNo())->get_sequence('OR');
                $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            } else {
                $generat_or_series = $request->payload['orcase_no'] ?? '';
            }
            $schedule = ORSchedulesModel::updateOrCreate(
                [
                'patient_id' => $patientid,'case_id' => $caseno_reg
                ],
                [
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
                // 'procedure_name' => $request->payload['procedurename'] ?? '',
                'remarks' => $request->payload['remarks'] ?? '',
                ]
            );

            if(count($request->payload['procedurename']) > 0) {
                foreach($request->payload['procedurename'] as $row) {
                    $schedule->procedures()->updateOrCreate(
                        [
                            'procedure_id' => $row['id'],
                            'schedule_id' => $id
                        ],
                        [
                            'procedure_id' => $row['id'],
                            'createdby' => Auth::user()->idnumber,
                        ]
                    );
                }
            }
            $scheduletoupdateprocedurename = collect($request->payload['procedurename'])->pluck('id');
            // Update the status of items not in the $scheduletoupdate list
            $schedule->procedures()
                    ->where('schedule_id', $id) // Include the timeslot_date to scope the
                    ->whereNotIn('procedure_id', $scheduletoupdateprocedurename)
                    ->update(['status' => 0]);


            if(count($request->payload['surgeon']) > 0) {
                foreach($request->payload['surgeon'] as $row) {
                    $schedule->scheduleSurgeons()->updateOrCreate(
                        [
                            'doctor_id' => $row['id'],'schedule_id' => $id
                        ],
                        [
                            'branch_id' => Auth::user()->branch_id,
                            'doctor_id' => $row['id'] ?? '',
                            'lastname' => $row['lastname'] ?? '',
                            'firstname' => $row['firstname'] ?? '',
                            'middlename' => $row['middlename'] ?? '',
                            'createdby' => Auth::user()->idnumber,
                        ]
                    );
                }
            }
            $scheduletoupdatesurgeon = collect($request->payload['surgeon'])->pluck('id');
            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduleSurgeons()
                    ->where('schedule_id', $id) // Include the timeslot_date to scope the
                    ->whereNotIn('doctor_id', $scheduletoupdatesurgeon)
                    ->update(['status' => 0]);


            if(count($request->payload['anesthesia']) > 0) {
                foreach($request->payload['anesthesia'] as $row) {
                    $schedule->scheduleAnesthesia()->updateOrCreate(
                        [
                       'doctor_id' =>  $row['id'],
                       'schedule_id' => $id
                    ],
                        [
                        'branch_id' => Auth::user()->branch_id,
                        'doctor_id' =>  $row['id'] ?? '',
                        'lastname' =>  $row['lastname'] ?? '',
                        'firstname' =>  $row['firstname'] ?? '',
                        'middlename' =>  $row['middlename'] ?? '',
                        'createdby' => Auth::user()->idnumber,
                    ]
                    );
                }

            }
            $scheduletoupdateanesthesia = collect($request->payload['anesthesia'])->pluck('id');
            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduleAnesthesia()
                    ->where('schedule_id', $id) // Include the timeslot_date to scope the
                    ->whereNotIn('doctor_id', $scheduletoupdateanesthesia)
                    ->update(['status' => 0]);

            if(count($request->payload['ORResident']) > 0) {
                foreach($request->payload['ORResident'] as $row) {
                    $schedule->scheduledResident()->updateOrCreate(
                        [
                            'doctor_id' => $row['id'],'schedule_id' => $id
                        ],
                        [
                            'doctor_id' => $row['id'] ?? '',
                            'doctor_name' => $row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename'],
                            'createdby' => Auth::user()->idnumber,
                        ]
                    );
                }
            }
            $scheduletoupdateORResident = collect($request->payload['ORResident'])->pluck('id');
            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduledResident()
                ->where('schedule_id', $id) // Include the timeslot_date to scope the
                ->whereNotIn('doctor_id', $scheduletoupdateORResident)
                ->update(['status' => 0]);


            if(isset($request->payload['radioScheduledTime']) && count($request->payload['radioScheduledTime']) > 0) {
                foreach($request->payload['radioScheduledTime'] as $key => $value) {
                    $schedule->scheduledRoomSlot()->updateOrCreate(
                        [
                        'schedule_id' => $id, 'timeslot_id' => $value, 'room_id' => $request->payload['or_room_id']
                        ],
                        [
                        'timeslot_date' => $request->payload['scheduleddate'] ?? '',
                        'timeslot_id' => $value,
                        'room_id' => $request->payload['or_room_id'] ?? '',
                        ]
                    );
                }
            }
            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduledRoomSlot()
                ->where('schedule_id', $id) // Include the timeslot_date to scope the
                ->whereNotIn('timeslot_id', $request->payload['radioScheduledTime'])
                ->update(['timeslot_id' => 0]);


            if(isset($request->payload['ORCirculatingNurses']) && count($request->payload['ORCirculatingNurses']) > 0) {
                foreach($request->payload['ORCirculatingNurses'] as $row) {
                    $schedule->scheduledCirculatingNurses()->updateOrCreate(
                        [
                            'operating_room_scheduled_id' => $id,
                            'empnum' => $row['empnum'],
                        ],
                        [
                            'empnum' =>  $row['empnum'] ?? '',
                            'branch_id' => Auth::user()->branch_id,
                            'firstname' => $row['firstname'] ?? '',
                            'lastname' => $row['lastname'] ?? '',
                            'middlename' => $row['middlename'] ?? '',
                            'specialty_id' => 1,
                            'createdby' => Auth::user()->idnumber,
                        ]
                    );
                }
            }
            $scheduletoupdateORCirculatingNurses = collect($request->payload['ORCirculatingNurses'])->pluck('empnum')->toArray();
            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduledCirculatingNurses()
                ->where('operating_room_scheduled_id', $id)
                ->where('specialty_id', 1)
                ->whereNotIn('empnum', $scheduletoupdateORCirculatingNurses)
                ->update(['status' => 0]);


            if (isset($request->payload['SNurse']) && count($request->payload['SNurse']) > 0) {
                foreach ($request->payload['SNurse'] as $row) {
                    $schedule->scheduledScrubNurses()->updateOrCreate(
                        [
                            'operating_room_scheduled_id' => $id,
                            'empnum' => $row['empnum'],
                        ],
                        [
                            'empnum' => $row['empnum'] ?? '',
                            'branch_id' => Auth::user()->branch_id,
                            'firstname' => $row['firstname'] ?? '',
                            'lastname' => $row['lastname'] ?? '',
                            'middlename' => $row['middlename'] ?? '',
                            'specialty_id' => 2,
                            'createdby' => Auth::user()->idnumber,
                        ]
                    );
                }


            }
            // Extract empnum values from the request for the WHERE NOT IN clause
            $scheduletoupdateSNurse = collect($request->payload['SNurse'])->pluck('empnum')->toArray();

            // Update the status of items not in the $scheduletoupdate list
            $schedule->scheduledScrubNurses()
                ->where('operating_room_scheduled_id', $id)
                ->where('specialty_id', 2)
                ->whereNotIn('empnum', $scheduletoupdateSNurse)
                ->update(['status' => 0]);

            if(!$id) {
                $seriesno = $or_sequenceno->seq_no + 1;
                $or_sequenceno->update([
                    'seq_no' => $seriesno,
                    'recent_generated' => $generat_or_series,
                ]);
            }

            $id = $schedule->id;
            // }

            DB::connection('sqlsrv_schedules')->commit();
            DB::connection('sqlsrv')->commit();
            $this->convertTOjson($id, null, $scheduleddate);
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

        }

    }

    public function convertTOjson($id, $status = null, $date = null)
    {
        $scheduleddate = Carbon::parse($date)->format('Y-m-d');
        $schedule_details = ORSchedulesModel::with('patientdetails', 'scheduledRoomSlot', 'scheduledCategory', 'station_details', 'scheduledStatus', 'procedures', 'scheduleAnesthesia', 'scheduleSurgeons', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses')
        ->whereDate('schedule_date', $scheduleddate)->where('id', $id)
        ->whereHas('scheduledRoomSlot', function ($query) {
            $query->where('timeslot_id', '!=', 0); // Use '!=' to check for not equal to 0
        })->first();

        if($schedule_details['category_id'] == '4') {
            $filename = 'scheduling/OPTHA-' . $scheduleddate . '.json';
        } else {
            $filename = 'scheduling/OR-' . $scheduleddate . '.json';
        }
        $dataArray = [];
        // Check if the data.json file exists
        if (Storage::disk('public')->exists($filename)) {
            // Read existing data from $filename.json
            $existingData = Storage::disk('public')->get($filename);
            // Decode the JSON data into an array
            $dataArray = json_decode($existingData, true);
        }

        $found = false;
        if(count($dataArray) > 0) {
            foreach ($dataArray as &$item) {
                if ($item['id'] === $id) {
                    // Update the details if the data already exists
                    $item['schedule_status_id'] = $schedule_details['schedule_status_id'];
                    $item['status'] = $schedule_details['scheduledStatus']['Status_description'];
                    $item['timeslots'] = [];
                    $item['schedule_date'] = $schedule_details['schedule_date'];
                    // $item['time'] = $schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['timeslot'];
                    $item['timeslots'] = $schedule_details['scheduledRoomSlot'];
                    $item['starttime'] = (int)$schedule_details['scheduledRoomSlot'][0]['scheduleTimeSlot']['start_time'];
                    $item['room_name'] = $schedule_details['scheduledRoomSlot'][0]['scheduleRoom']['room_name'];
                    $item['procedure_name'] = $schedule_details['procedures'];
                    $item['remarks'] = $schedule_details['remarks'];
                    $item['surgeons'] = $schedule_details['scheduleSurgeons'];
                    $item['anesthesia'] = $schedule_details['scheduleAnesthesia'];
                    $item['resident'] = $schedule_details['scheduledResident'];
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
        }
        if (!$found) {
            $newData = [
                'id' => $schedule_details['id'],
                'patient_id' => $schedule_details['patient_id'],
                'case_id' => $schedule_details['case_id'],
                'orcase_no' => $schedule_details['orcase_no'],
                'patientname' => $schedule_details['patientdetails']['patient_name'],
                'schedule_date' => $schedule_details['schedule_date'],
                'timeslots' => $schedule_details['scheduledRoomSlot'],
                // 'time' => $schedule_details['scheduledRoomSlot']['scheduleTimeSlot']['timeslot'],
                'starttime' => (int)$schedule_details['scheduledRoomSlot'][0]['scheduleTimeSlot']['start_time'],
                'room_name' => $schedule_details['scheduledRoomSlot'][0]['scheduleRoom']['room_name'],
                'procedure_name' => $schedule_details['procedures'],
                'remarks' => $schedule_details['remarks'],
                'surgeons' => $schedule_details['scheduleSurgeons'],
                'anesthesia' => $schedule_details['scheduleAnesthesia'],
                'resident' => $schedule_details['scheduledResident'],
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
            $date = Request()->date;
            // Update the ORSchedulesModel record
            $orshedule = ORSchedulesModel::where('id', $payload['id'])->first();
            $orshedule->update([
                'schedule_status_id' => $payload['status_id'],
                'isprocessed' => 1,
                'processedBy' => Auth::user()->idnumber,
                'processed_date' => Carbon::now(),
            ]);
            ORRegistration::where('patient_id',$orshedule->patient_id)->where('case_id',$orshedule->case_id)->update([
                'status_id'=>$payload['status_id']
            ]);
            // Update or create the OperatingRoomScheduleStatusChangeLog record
            // $checklogs = OperatingRoomScheduleStatusChangeLog::where('operating_room_scheduled_id', $payload['id'])
            // ->where('previous_status_id', $payload['previous_status_id'])
            // ->where('new_status_id', $payload['status_id'])->first();
            // if($checklogs) {
            //     $checklogs->update([
            //        'doctor_id' => $payload['doctor_id'],
            //        'operating_room_scheduled_id' => $payload['id'],
            //        'previous_status_id' => $payload['previous_status_id'],
            //        'new_status_id' => $payload['status_id'],
            //        'updatedby' => Auth::user()->idnumber,
            //     ]);
            // } else {
            //     OperatingRoomScheduleStatusChangeLog::create([
            //         'doctor_id' => $payload['doctor_id'],
            //         'operating_room_scheduled_id' => $payload['id'],
            //         'previous_status_id' => $payload['previous_status_id'],
            //         'new_status_id' => $payload['status_id'],
            //         'createdby' => Auth::user()->idnumber,
            //     ]);
            // }

            DB::connection('sqlsrv_schedules')->commit();
            $this->convertTOjson($payload['id'], $payload['previous_status_id'], $orshedule->schedule_date);
            return response()->json(["message" =>  'Transfer to Waiting Room successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function updateseletedtimeslot()
    {

        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {

            // $schedule = ORRoomTimSlotTransactionModel::updateOrCreate([

            // ])

            // $ORScheduledModel = ORRoomTimSlotTransactionModel::where('timeslot_id', Request()->timeslot_id)->where('room_id', Request()->room_id)->where('schedule_id', Request()->schedule_id)->whereDate('timeslot_date', Carbon::parse(Request()->schedule_date)->format('Y-m-d'))->first();
            // if($ORScheduledModel) {
            //     $ORScheduledModel->update([
            //         'timeslot_id' => 0,
            //     ]);
            // } else {
            //     $ORScheduledModel = ORRoomTimSlotTransactionModel::where('timeslot_id', 0)->where('room_id', Request()->room_id)->where('schedule_id', Request()->schedule_id)->whereDate('timeslot_date', Carbon::parse(Request()->schedule_date)->format('Y-m-d'))->first();
            //     if($ORScheduledModel) {
            //         $ORScheduledModel->update([
            //             'timeslot_id' => Request()->timeslot_id,
            //         ]);
            //     } else {

            //     ORRoomTimSlotTransactionModel::create(
            //     [
            //         'timeslot_id' => Request()->timeslot_id,
            //         'room_id' => Request()->room_id,
            //         'schedule_id' => Request()->schedule_id,
            //         'timeslot_date' => Carbon::parse(Request()->schedule_date)->format('Y-m-d'),
            //     ]);

            //     }
            // }
            DB::connection('sqlsrv_schedules')->commit();
            $this->convertTOjson(Request()->schedule_id, null, Request()->schedule_date);
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function submitprocedure()
    {
        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {

            OperatingRoomProcedures::create([
                'descriptions' => Request()->payload['procedurename'] ?? '',
                'createdby' => Auth::user()->idnumber,
                'isactive' => 1,
            ]);

            DB::connection('sqlsrv_schedules')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function bookappointment(Request $request)
    {
        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        // DB::connection('sqlsrv_medsys_patientdatacdg')->beginTransaction();
        try {

            $payload = $request->payload;
            $date = Carbon::parse($payload['date'])->format('Y-m-d');
            $checkpatientifexist = ORPatientModel::where('LastName', 'like', '%' . $payload['lastname'] . '%')->where('FirstName', 'like', '%' . $payload['firstname'] . '%')
            ->whereDate('BirthDate', Carbon::parse($payload['birthdate'])->format('Y-m-d'))->select('LastName', 'FirstName', 'BirthDate', 'HospNum')
            ->first();

            if(!$payload['patientid']) {
                DB::connection('sqlsrv_medsys_patientdatacdg')->select("SET NOCOUNT ON ; EXEC sp_get_medsys_sequence 'opdid'");
                if(!$checkpatientifexist) {
                    DB::connection('sqlsrv_medsys_patientdatacdg')->select("SET NOCOUNT ON ; EXEC sp_get_medsys_sequence 'hospnum'");
                    $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'OPDId')->first();
                    $generated_medsys_patient_id_no = $check_medsys_series_no->HospNum;
                    $generated_medsys_patient_case_id_no = $check_medsys_series_no->OPDId . 'B';
                    
                        ORPatientModel::insert([
                            'HospNum' => $generated_medsys_patient_id_no,
                            'LastName' => $payload['lastname'],
                            'FirstName' => $payload['firstname'],
                            'MiddleName' => $payload['middlename'],
                            'Sex' => $payload['gender'],
                            'BirthDate' => Carbon::parse($payload['birthdate'])->format('Y-m-d')
                        ]);

                } else {
                    $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'OPDId')->first();
                    $generated_medsys_patient_id_no = $checkpatientifexist->HospNum;
                    $generated_medsys_patient_case_id_no = $check_medsys_series_no->OPDId . 'B';
                   
                }
            } else {
                $generated_medsys_patient_id_no = $payload['patientid'];
                $generated_medsys_patient_case_id_no = $payload['caseid'];
            }

            // Find or create the record based on the conditions
            $registration = ORRegistration::updateOrCreate(
                [
                    'patient_id' => $generated_medsys_patient_id_no,
                    'case_id' => $generated_medsys_patient_case_id_no,
                    // 'lastname' => $payload['lastname'],
                    // 'firstname' => $payload['firstname'],
                    // 'middlename' => $payload['middlename'],
                    // 'gender' => $payload['gender'],
                    // 'birthdate' => Carbon::parse($payload['birthdate'])->format('Y-m-d'),
                ],
                [
                    'patient_id' => $generated_medsys_patient_id_no,
                    'case_id' => $generated_medsys_patient_case_id_no,
                    'lastname' => $payload['lastname'] ?? '',
                    'firstname' => $payload['firstname'] ?? '',
                    'middlename' => $payload['middlename'] ?? '',
                    'gender' => $payload['gender'] ?? '',
                    'date_schedule' => $date,
                    'mobileno' => $payload['mobileno'] ?? '',
                    'remarks' => $payload['remarks'] ?? '',
                    'status_id' => '14',
                    'registered_by' => Auth::user()->idnumber,
                    'createdby' => Auth::user()->idnumber,
                    'birthdate' => Carbon::parse($payload['birthdate'])->format('Y-m-d'),
                ]
            );
            
            ORRegistrationProcedures::where('patient_id', $generated_medsys_patient_id_no)->whereDate('date_schedule',$date)->delete();
            if(count($payload['procedure']) > 0) {
                foreach($payload['procedure'] as $row) {
                    $registration->procedures()->updateOrCreate(
                        [
                           'procedure_id' => $row,
                           'patient_id' => $generated_medsys_patient_id_no,
                           'date_schedule' => $date,
                        ],
                        [
                           'patient_id' => $generated_medsys_patient_id_no,
                           'procedure_id' => $row,
                           'date_schedule' => $date,
                        ]
                    );
                }
            }

            ORRegistrationTimeSlot::where('patient_id', $generated_medsys_patient_id_no)->whereDate('timeslot_date',$date)->delete();
            if(count($payload['time']) > 0) {
                foreach($payload['time'] as $row) {
                    $registration->timeslots()->updateOrCreate(
                        [
                           'patient_id' => $generated_medsys_patient_id_no,
                           'timeslot_id' => $row,
                           'timeslot_date' => $date,
                        ],
                        [
                           'patient_id' => $generated_medsys_patient_id_no,
                           'timeslot_id' => $row,
                           'timeslot_date' => $date,
                        ]
                    );
                }
            }
            
            ORRegistrationPreferredSurgeon::where('patient_id', $generated_medsys_patient_id_no)->whereDate('date_schedule',$date)->delete();
            if(count($payload['surgeon']) > 0) {
                foreach($payload['surgeon'] as $row) {
                    $registration->surgeon()->updateOrCreate(
                        [
                        'patient_id' => $generated_medsys_patient_id_no,
                        'surgeon_id' => $row,
                        'date_schedule' => $date,
                        ],
                        [
                        'patient_id' => $generated_medsys_patient_id_no,
                        'surgeon_id' => $row,
                        'date_schedule' => $date,
                        ]
                    );
                }
            }
            
            ORRegistrationPreferredAnesthesia::where('patient_id', $generated_medsys_patient_id_no)->whereDate('date_schedule',$date)->delete();
            if(count($payload['anesthesia']) > 0) {
                foreach($payload['anesthesia'] as $row) {
                    $registration->anesthesia()->updateOrCreate(
                        [
                        'patient_id' => $generated_medsys_patient_id_no,
                        'anesthesia_id' => $row,
                        'date_schedule' => $date,
                        ],
                        [
                        'patient_id' => $generated_medsys_patient_id_no,
                        'anesthesia_id' => $row,
                        'date_schedule' => $date,
                        ]
                    );
                }
            }

            
            // Extract empnum values from the request for the WHERE NOT IN clause
            // Update the status of items not in the $scheduletoupdate list
           

            // Convert arrays to string representation before updating

            // Update the fields, whether it's a new or existing record

            DB::connection('sqlsrv_schedules')->commit();
            DB::connection('sqlsrv')->commit();
            // DB::connection('sqlsrv_medsys_patientdatacdg')->commit();

            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            DB::connection('sqlsrv')->rollback();
            // DB::connection('sqlsrv_medsys_patientdatacdg')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);

        }
    }

    public function registration()
    {
        try {
            $data = ORRegistration::query();
            $page  = Request()->per_page ?? '1';
            if(Request()->keyword) {
                $data->where('lastname', 'LIKE', '%' . Request()->keyword . '%')->orWhere('firstname', 'LIKE', '%' . Request()->keyword . '%');
            }
            if(Request()->date) {
                $data->whereDate('date_schedule', Request()->date);
            }

            if(Auth()->user()->role['name'] == 'Doctor') {
                $data->where('registered_by', Auth()->user()->idnumber);
            }
            if(Auth()->user()->role['name'] == 'Scrub Nurse') {
                $data->where('status_id', 14);
            }
            $data->orderBy('id', 'desc');
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
