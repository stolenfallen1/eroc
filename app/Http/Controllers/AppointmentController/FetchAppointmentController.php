<?php

namespace App\Http\Controllers\AppointmentController;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\HIS\PatientAppointments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FetchAppointmentController extends Controller
{
    public function getAppointmentPatient(Request $request)
    {

        $payload = $request->all();


        $patient = PatientAppointmentsTemporary::select('patient_id', 'id')->where('user_id', $payload['id'])->first();
        $patient_id = $patient->patient_id;
        $id = $patient->id;

        try {

            $data = PatientAppointments::with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity', 'id')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
            ])
                ->orderBy('created_at', 'desc')
                ->where(function ($query) use ($patient_id, $id) {
                    $query->where('temporary_Patient_Id', $id)
                        ->orWhere('patient_id', $patient_id);
                })
                ->whereIn('status_Id', [1,2, 3])->get();

            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Error fetching appointment data: ' . $e->getMessage());

            // Return a structured error response
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500); // Internal Server Error status code
        }
    }
    public function getDoneAppointmentPatient(Request $request)
    {

        $payload = $request->all();
        $patient = PatientAppointmentsTemporary::select('patient_id', 'id')->where('user_id', $payload['id'])->first();
        $patient_id = $patient->patient_id;
        $id = $patient->id;

        try {
          
            $data = PatientAppointments::with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity', 'id')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
            ])
                ->orderBy('created_at', 'desc')
                ->where(function ($query) use ($patient_id, $id) {
                    $query->where('temporary_Patient_Id', $id)
                        ->orWhere('patient_id', $patient_id);
                })
                ->whereIn('status_Id', [0])->get();

            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAppointmentCashier(Request $request)
    {
        $payload = $request->all();
        $selectDate = Carbon::parse($payload['selectedDate'])->format('Y-m-d') ?? Carbon::today()->format('Y-m-d');
      
        try {
            $data = PatientAppointments::where('appointment_Date', $selectDate)->with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
                'appointmentsTemporary' => function ($query) {
                    $query->select('patient_Id', 'birthdate', 'lastname', 'firstname', 'email_address', 'mobile_number', 'id', 'sex_Id')
                        ->addSelect([
                            'age' => DB::raw('DATEDIFF(YEAR, birthdate, GETDATE())')
                        ]);
                }
            ])
                ->orderBy('created_at', 'desc')
                ->whereIn('status_Id', [0,1,2])
                ->whereHas('appointmentPayments')
                ->get();

            return response()->json(['data' => $data ,'date' => $selectDate], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAppointmentRecieptionist(Request $request)
    {
        $payload = $request->all();
        $selectDate = Carbon::parse($payload['selectedDate'])->format('Y-m-d') ?? Carbon::today()->format('Y-m-d');
     
        try {
            $data = PatientAppointments::where('appointment_Date', $selectDate)->with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
                'appointmentsTemporary' => function ($query) {
                    $query->select('patient_Id', 'birthdate', 'lastname', 'firstname', 'email_address', 'mobile_number', 'id', 'sex_Id')
                        ->addSelect([
                            'age' => DB::raw('DATEDIFF(YEAR, birthdate, GETDATE())')
                        ]);
                }
            ])
                ->orderBy('created_at', 'desc')
                ->whereIn('status_Id', [1])
                ->whereHas('appointmentPayments')
                ->doesntHave('checkIn')
                ->get();

            return response()->json(['data'=> $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAppointmentCheckInRecieptionist(Request $request)
    {
        $payload = $request->all();
        $selectDate = Carbon::parse($payload['selectedDate'])->format('Y-m-d') ?? Carbon::today()->format('Y-m-d');
      
        try {
            $data = PatientAppointments::where('appointment_Date', $selectDate)->with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
                'appointmentsTemporary' => function ($query) {
                    $query->select('patient_Id', 'birthdate', 'lastname', 'firstname', 'email_address', 'mobile_number', 'id', 'sex_Id')
                        ->addSelect([
                            'age' => DB::raw('DATEDIFF(YEAR, birthdate, GETDATE())')
                        ]);
                },
                'checkIn'
            ])
                ->orderBy('created_at', 'asc')
                ->whereIn('status_Id', [1])
                ->whereHas('appointmentPayments')
                ->whereHas('checkIn')
                ->get();
            return response()->json(['data' => $data],200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDoneAppointmentCheckInRecieptionist(Request $request)
    {
        $payload = $request->all();
        $selectDate = Carbon::parse($payload['selectedDate'])->format('Y-m-d') ?? Carbon::today()->format('Y-m-d');
     
        try {
            $data = PatientAppointments::where('appointment_Date', $selectDate)->with([
                'doctors' => function ($query) {
                    $query->select('lastname', 'firstname', 'id');
                },
                'centers' => function ($query) {
                    $query->select('title', 'id');
                },
                'sections' => function ($query) {
                    $query->select('id', 'section_name');
                },
                'appointmentTransactions' => function ($query) {
                    $query->select('appointment_ReferenceNumber', 'transDate', 'transaction_Code', 'item_Id', 'quantity')
                        ->with([
                            'items' => function ($query) {
                                $query->select('map_item_id', 'exam_resultName', 'id')
                                    ->with([
                                        'prices' => function ($query) {
                                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                                ->where('msc_price_scheme_id', 1);
                                        }
                                    ]);
                            }
                        ]);
                },
                'appointmentPayments',
                'appointmentsTemporary' => function ($query) {
                    $query->select('patient_Id', 'birthdate', 'lastname', 'firstname', 'email_address', 'mobile_number', 'id', 'sex_Id')
                        ->addSelect([
                            'age' => DB::raw('DATEDIFF(YEAR, birthdate, GETDATE())')
                        ]);
                },
                'checkIn'
            ])
                ->orderBy('created_at', 'desc')
                ->whereIn('status_Id', [0])
                ->whereHas('appointmentPayments')
                ->whereHas('checkIn')
                ->get();
            return response()->json(['data' => $data],200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch appointment data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
