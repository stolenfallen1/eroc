<?php

namespace App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Controller;
use App\Models\Appointments\AppointmentCenter;
use App\Models\BuildFile\address\Province;
use App\Models\BuildFile\FmsExamProcedureItems;
use App\Models\BuildFile\Hospital\CivilStatus;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\Nationalities;
use Illuminate\Support\Facades\DB;

class FieldDetails extends Controller
{
    public function getRegion()
    {

        $regions = DB::table('mscAddressRegions')->select('region_name', 'region_code')->where('isactive', 1)->get();
        $civil_status = DB::table('mscCivilStatuses')->select('id', 'civil_status_description')->where('isactive')->first();
        return response()->json(['regions' => $regions, 'civil_status' => $civil_status]);
    }

    public function getProvinces($region_code)
    {
        $provinces = Province::select('province_name', 'province_code')
            ->where('region_code', $region_code)
            ->where('isactive', 1)
            ->with([
                'municipalities' => function ($query) {
                    $query->select('municipality_name', 'municipality_code', 'province_code')
                        ->with(['barangays' => function ($query) {
                            $query->select('barangay_name', 'id', 'municipality_code');
                        }])->where('isactive',1)
                        ->with(['zipcodes' => function ($query) {
                            $query->select('zip_code', 'municipality_code', 'province_code');
                        }])->where('isactive',1);
                }
            ])
            ->get();

        return response()->json(['provinces' => $provinces], 200);
    }

    public function getCivil()
    {
        $nationalities = Nationalities::select('id', 'nationality_code', 'nationality')->where('isActive', 1)->get();
        $civil_status = CivilStatus::select('id', 'civil_status_description')->where('isActive', 1)->get();
        return response()->json(['civilStatus' => $civil_status, 'nationalities' => $nationalities]);
    }

    public function getDoctors()
    {
        $doctors = Doctor::where('isactive', 1)
            ->get()
            ->groupBy(function ($doctor) {
                return $doctor->lastname . ' ' . $doctor->firstname;
            })
            ->map(function ($group) {
                return $group->first();
            })
            ->sortBy(function ($doctor) {
                return $doctor->lastname . ' ' . $doctor->firstname;
            })
            ->values();
        $imageUrl = asset('images/logo1.png');

        return response()->json(['doctors' => $doctors, 'image_url' => $imageUrl], 200);
    }

    public function getAppointmentCenter()
    {
        $centers = AppointmentCenter::where('isactive', 1)
            ->with([
                'procedures' => function ($query) {
                    $query->select('id', 'transaction_code', 'map_item_id',  'exam_resultName', 'map_revenue_code', 'id')
                        ->where('isactive', 1)
                        ->with(['prices' => function ($query) {
                            $query->select('price', 'msc_price_scheme_id', 'examprocedure_id')
                                ->where('msc_price_scheme_id', 1);
                        }]);
                },
                'sections' => function ($query) {
                    $query->select('appointment_center_id', 'id', 'section_name', 'section_code', 'description')
                        ->where('isactive', 1);
                }
            ])
            ->select('id', 'title', 'icon', 'revenueID')
            ->get();


        return response()->json(['centers' => $centers], 200);
    }
    public function getProcedure($trans_code)
    {
        $procedures = FmsExamProcedureItems::where('transaction_code', $trans_code)
            ->with(['prices' => function ($query) {
                $query->select('examprocedure_id', 'price', 'msc_price_scheme_id')->where('msc_price_scheme_id', 1); // Select relevant fields for prices
            }])
            ->select('id', 'transaction_code', 'exam_description', 'exam_resultName') // Select fields from FmsExamProcedureItems
            ->where('isactive', 1)
            ->get();

        return response()->json(['procedures' => $procedures]);
    }

}
