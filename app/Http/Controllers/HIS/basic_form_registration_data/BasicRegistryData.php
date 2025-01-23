<?php

namespace App\Http\Controllers\HIS\basic_form_registration_data;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Models\HIS\mscComplaint;
use App\Models\HIS\mscServiceType;
use App\Models\HIS\mscPatientBroughtBy;

class BasicRegistryData extends Controller {
    //
    public function getPatientBroughtBy() {
        try {
            $data = mscPatientBroughtBy::select('id', 'description')->orderBy('id', 'ASC')->get();
            if($data->isEmpty()) return response()->json([], 404);
            $patientBroughtBy = $data->map(function($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($patientBroughtBy, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }

    public function getDisposition() {
        try {
            $data =  DB::connection('sqlsrv')->table('mscDispositions')
                ->select('id', 'disposition_description')
                ->orderBy('disposition_description','asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $dispositions = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->disposition_description,
                ];
            });
            return response()->json($dispositions, 200);
        } catch(\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }

    public function getComplaintList() {
        try {
            $data = mscComplaint::select('id', 'description')
                ->where('isActive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscComplaints = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscComplaints, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }

    public function getServiceType() {
        try {
            $data = mscServiceType::select('id', 'description')
                ->where('isactive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscServiceType = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscServiceType, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }
}
