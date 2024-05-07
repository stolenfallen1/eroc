<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Doctor;

class DoctorController extends Controller
{
    public function index()
    {

        try {
            $data = Doctor::query();
            $data->with("doctorAddress","doctorClinicAddress");
            if(!is_numeric(Request()->keyword)) {
                $patientname = Request()->keyword ?? '';
                $names = explode(',', $patientname); // Split the keyword into firstname and lastname
                $last_name = $names[0];
                $first_name = $names[1]  ?? '';
                if($last_name != '' && $first_name != '') {
                    $data->where('lastname', $last_name);
                    $data->where('firstname', 'LIKE', '' . ltrim($first_name) . '%');
                } else {
                    $data->where('lastname', 'LIKE', '' . $last_name . '%');
                }
            } else {
                $data->where('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('isactive', 'desc')->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function list()
    {
        try {

            $query = Doctor::with("doctorAddress", "doctorClinicAddress")->where('isactive', 1);
            if(Request()->lastname) {
                $query->where('lastname', 'LIKE', '' . Request()->lastname . '%');
            }
            if(Request()->firstname) {
                $query->where('firstname', 'LIKE', '' . Request()->firstname . '%');
            }
            if(Request()->middlename) {
                $query->where('middlename', 'LIKE', '' . Request()->middlename . '%');
            }
            if(Request()->birthdate) {
                $query->whereDate('birthdate', carbon::parse(Request()->birthdate)->format('Y-m-d'));
            }
            $data = $query->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }



    public function store(Request $request)
    {

        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = Request()->payload;
            if(!Doctor::where('doctor_code',$payload["doctor_code"])->exists()){
                $doctors = Doctor::create([
                   "doctor_code"=>isset($payload["doctor_code"]) ? $payload["doctor_code"] : NULL, 
                    "lastname"=>isset($payload["lastname"]) ? $payload["lastname"] : NULL, 
                    "firstname"=>isset($payload["firstname"]) ? $payload["firstname"] : NULL, 
                    "middlename"=>isset($payload["middlename"]) ? $payload["middlename"] : NULL, 
                    "suffix_id"=>isset($payload["suffix_id"]) ? $payload["suffix_id"] : NULL, 
                    "birthdate"=>isset($payload["birthdate"]) ? $payload["birthdate"] : NULL, 
                    "age"=>isset($payload["age"]) ? $payload["age"] : NULL, 
                    "civil_status_id"=>isset($payload["civil_status_id"]) ? $payload["civil_status_id"] : NULL, 
                    "mobile_no"=>isset($payload["mobile_no"]) ? $payload["mobile_no"] : NULL, 
                    "telephoneno"=>isset($payload["telephoneno"]) ? $payload["telephoneno"] : NULL,
                    "email"=>isset($payload["email"]) ? $payload["email"] : NULL, 
                    "sex_id"=>isset($payload["sex_id"]) ? $payload["sex_id"] : NULL, 
                    "TIN"=>isset($payload["TIN"]) ? $payload["TIN"] : NULL, 
                    "bank_account_name"=>isset($payload["bank_account_name"]) ? $payload["bank_account_name"] : NULL, 
                    "bank_account_no"=>isset($payload["bank_account_no"]) ? $payload["bank_account_no"] : NULL, 
                    "ptr_no"=>isset($payload["ptr_no"]) ? $payload["ptr_no"] : NULL, 
                    "s2_no"=>isset($payload["s2_no"]) ? $payload["s2_no"] : NULL, 
                    "prc_license_expiry_date"=>isset($payload["prc_license_expiry_date"]) ? $payload["prc_license_expiry_date"] : NULL, 
                    "prc_license_no"=>isset($payload["prc_license_no"]) ? $payload["prc_license_no"] : NULL, 
                    "philhealth_accreditation_no"=>isset($payload["philhealth_accreditation_no"]) ? $payload["philhealth_accreditation_no"] : NULL, 
                    "philhealth_accreditation_expiry_date"=>isset($payload["philhealth_accreditation_expiry_date"]) ? $payload["philhealth_accreditation_expiry_date"] : NULL, 
                    "pmcc_no"=>isset($payload["pmcc_no"]) ? $payload["pmcc_no"] : NULL, 
                    "professional_fee_vat_rate"=>isset($payload["professional_fee_vat_rate"]) ? $payload["professional_fee_vat_rate"] : NULL, 
                    "readers_fee_vat_rate"=>isset($payload["readers_fee_vat_rate"]) ? $payload["readers_fee_vat_rate"] : NULL, 
                    "service_type"=>isset($payload["service_type"]) ? $payload["service_type"] : NULL, 
                    "specialization_primary_id"=>isset($payload["specialization_primary_id"]) ? $payload["specialization_primary_id"] : NULL, 
                    "category_id"=>isset($payload["category_id"]) ? $payload["category_id"] : NULL, 
                    "doctor_code"=>isset($payload["doctor_code"]) ? $payload["doctor_code"] : NULL, 
                    "WithHolding__tax_rate"=>isset($payload["WithHolding__tax_rate"]) ? $payload["WithHolding__tax_rate"] : NULL,
                    "phic_group_id"=>isset($payload["phic_group_id"]) ? $payload["phic_group_id"] : NULL,
                    "prc_type_id"=>isset($payload["prc_type_id"]) ? $payload["prc_type_id"] : NULL,
                    "residential_address_id"=>isset($payload["residential_address_id"]) ? $payload["residential_address_id"] : NULL,
                    "class_code_id"=>isset($payload["class_code_id"]) ? $payload["class_code_id"] : NULL,
                    "isVatable"=>isset($payload["isVatable"]) ? $payload["isVatable"] : NULL,
                    "service_class_id"=>isset($payload["service_class_id"]) ? $payload["service_class_id"] : NULL,
                    "isactive" =>isset($payload["isactive"]) ? $payload["isactive"] : 0,
                    "createdBy"=>Auth()->user()->idnumber,
                    "created_at"=>Carbon::now(),
                ]);

                if(isset($payload['residentialaddress'])){
                        $doctors->doctorAddress()->create([
                        "full_address" => isset($payload['residentialaddress']) ? $payload['residentialaddress'] : null,
                        "building" => isset($payload['residential_building']) ? $payload['residential_building'] : null,
                        "barangay_id" => isset($payload['residential_barangay_id']) ? $payload['residential_barangay_id'] : null,
                        "municipality_id" => isset($payload['residential_municipality_id']) ? $payload['residential_municipality_id'] : null,
                        "province_id" => isset($payload['residential_province_id']) ? $payload['residential_province_id'] : null,
                        "region_id" => isset($payload['residential_region_id']) ? $payload['residential_region_id'] : null,
                        "country_id" => isset($payload['residential_country_id']) ? $payload['residential_country_id'] : null,
                        "zipcode_id" => isset($payload['residential_zicode_id']) ? $payload['residential_zicode_id'] : null,
                    ]);
                }
               
                if(isset($payload['clinicaddress'])){
                    $doctors->doctorClinicAddress()->create([
                    "full_address" => isset($payload['clinicaddress']) ? $payload['clinicaddress'] : null,
                    "building" => isset($payload['clinic_building']) ? $payload['clinic_building'] : null,
                    "barangay_id" => isset($payload['clinic_barangay_id']) ? $payload['clinic_barangay_id'] : null,
                    "municipality_id" => isset($payload['clinic_municipality_id']) ? $payload['clinic_municipality_id'] : null,
                    "province_id" => isset($payload['clinic_province_id']) ? $payload['clinic_province_id'] : null,
                    "region_id" => isset($payload['clinic_region_id']) ? $payload['clinic_region_id'] : null,
                    "country_id" => isset($payload['clinic_country_id']) ? $payload['clinic_country_id'] : null,
                    "zipcode_id" => isset($payload['clinic_zicode_id']) ? $payload['clinic_zicode_id'] : null,
                    ]);
                }
              
                DB::connection('sqlsrv')->commit();
                return response()->json(['msg' => 'Record successfully saved'], 200);

            }
            return response()->json(['msg' => 'Doctor Code already exists'], 200);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request,$id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = Request()->payload;
            $doctors = Doctor::where('id',$payload['id'])->first();
            $doctors->update([
                "doctor_code"=>isset($payload["doctor_code"]) ? $payload["doctor_code"] : NULL, 
                "lastname"=>isset($payload["lastname"]) ? $payload["lastname"] : NULL, 
                "firstname"=>isset($payload["firstname"]) ? $payload["firstname"] : NULL, 
                "middlename"=>isset($payload["middlename"]) ? $payload["middlename"] : NULL, 
                "suffix_id"=>isset($payload["suffix_id"]) ? $payload["suffix_id"] : NULL, 
                "birthdate"=>isset($payload["birthdate"]) ? $payload["birthdate"] : NULL, 
                "age"=>isset($payload["age"]) ? $payload["age"] : NULL, 
                "civil_status_id"=>isset($payload["civil_status_id"]) ? $payload["civil_status_id"] : NULL, 
                "mobile_no"=>isset($payload["mobile_no"]) ? $payload["mobile_no"] : NULL, 
                "telephoneno"=>isset($payload["telephoneno"]) ? $payload["telephoneno"] : NULL,
                "email"=>isset($payload["email"]) ? $payload["email"] : NULL, 
                "sex_id"=>isset($payload["sex_id"]) ? $payload["sex_id"] : NULL, 
                "TIN"=>isset($payload["TIN"]) ? $payload["TIN"] : NULL, 
                "bank_account_name"=>isset($payload["bank_account_name"]) ? $payload["bank_account_name"] : NULL, 
                "bank_account_no"=>isset($payload["bank_account_no"]) ? $payload["bank_account_no"] : NULL, 
                "ptr_no"=>isset($payload["ptr_no"]) ? $payload["ptr_no"] : NULL, 
                "s2_no"=>isset($payload["s2_no"]) ? $payload["s2_no"] : NULL, 
                "prc_license_expiry_date"=>isset($payload["prc_license_expiry_date"]) ? $payload["prc_license_expiry_date"] : NULL, 
                "prc_license_no"=>isset($payload["prc_license_no"]) ? $payload["prc_license_no"] : NULL, 
                "philhealth_accreditation_no"=>isset($payload["philhealth_accreditation_no"]) ? $payload["philhealth_accreditation_no"] : NULL, 
                "philhealth_accreditation_expiry_date"=>isset($payload["philhealth_accreditation_expiry_date"]) ? $payload["philhealth_accreditation_expiry_date"] : NULL, 
                "pmcc_no"=>isset($payload["pmcc_no"]) ? $payload["pmcc_no"] : NULL, 
                "professional_fee_vat_rate"=>isset($payload["professional_fee_vat_rate"]) ? $payload["professional_fee_vat_rate"] : NULL, 
                "readers_fee_vat_rate"=>isset($payload["readers_fee_vat_rate"]) ? $payload["readers_fee_vat_rate"] : NULL, 
                "service_type"=>isset($payload["service_type"]) ? $payload["service_type"] : NULL, 
                "specialization_primary_id"=>isset($payload["specialization_primary_id"]) ? $payload["specialization_primary_id"] : NULL, 
                "category_id"=>isset($payload["category_id"]) ? $payload["category_id"] : NULL, 
                "doctor_code"=>isset($payload["doctor_code"]) ? $payload["doctor_code"] : NULL, 
                "WithHolding__tax_rate"=>isset($payload["WithHolding__tax_rate"]) ? $payload["WithHolding__tax_rate"] : NULL,
                "phic_group_id"=>isset($payload["phic_group_id"]) ? $payload["phic_group_id"] : NULL,
                "prc_type_id"=>isset($payload["prc_type_id"]) ? $payload["prc_type_id"] : NULL,
                "residential_address_id"=>isset($payload["residential_address_id"]) ? $payload["residential_address_id"] : NULL,
                "class_code_id"=>isset($payload["class_code_id"]) ? $payload["class_code_id"] : NULL,
                "isVatable"=>isset($payload["isVatable"]) ? $payload["isVatable"] : NULL,
                "service_class_id"=>isset($payload["service_class_id"]) ? $payload["service_class_id"] : NULL,
                "isactive" =>isset($payload["isactive"]) ? $payload["isactive"] : 0,
                "updatedBy"=>Auth()->user()->idnumber,
            ]);
            
            if(isset($payload['residentialaddress'])) {
                $doctors->doctorAddress()->update([
                    "full_address" => isset($payload['residentialaddress']) ? $payload['residentialaddress'] : null,
                ]);
                if(isset($payload['residential_building'])) {
                    $doctors->doctorAddress()->update([
                        "building" => isset($payload['residential_building']) ? $payload['residential_building'] : null,
                        "barangay_id" => isset($payload['residential_barangay_id']) ? $payload['residential_barangay_id'] : null,
                        "municipality_id" => isset($payload['residential_municipality_id']) ? $payload['residential_municipality_id'] : null,
                        "province_id" => isset($payload['residential_province_id']) ? $payload['residential_province_id'] : null,
                        "region_id" => isset($payload['residential_region_id']) ? $payload['residential_region_id'] : null,
                        "country_id" => isset($payload['residential_country_id']) ? $payload['residential_country_id'] : null,
                        "zipcode_id" => isset($payload['residential_zicode_id']) ? $payload['residential_zicode_id'] : null,
                    ]);
                }
            }

            if(isset($payload['clinicaddress'])) {
                $doctors->doctorClinicAddress()->update([
                    "full_address" => isset($payload['clinicaddress']) ? $payload['clinicaddress'] : null,
                ]);
                if(isset($payload['clinic_building'])) {
                    $doctors->doctorAddress()->update([
                        "building" => isset($payload['clinic_building']) ? $payload['clinic_building'] : null,
                        "barangay_id" => isset($payload['clinic_barangay_id']) ? $payload['clinic_barangay_id'] : null,
                        "municipality_id" => isset($payload['clinic_municipality_id']) ? $payload['clinic_municipality_id'] : null,
                        "province_id" => isset($payload['clinic_province_id']) ? $payload['clinic_province_id'] : null,
                        "region_id" => isset($payload['clinic_region_id']) ? $payload['clinic_region_id'] : null,
                        "country_id" => isset($payload['clinic_country_id']) ? $payload['clinic_country_id'] : null,
                        "zipcode_id" => isset($payload['clinic_zicode_id']) ? $payload['clinic_zicode_id'] : null,
                    ]);
                }
            }

            DB::connection('sqlsrv')->commit();
            return response()->json(['msg' => 'Record successfully update'], 200);
        } catch (\Exception $e) {

            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
