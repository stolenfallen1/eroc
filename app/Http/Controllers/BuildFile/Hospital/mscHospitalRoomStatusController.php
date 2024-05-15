<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalRoomsStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class mscHospitalRoomStatusController extends Controller
{
    public function index() {
        try {
            $data = mscHospitalRoomsStatus::query();
            if(Request()->keyword) {
                $data->where('room_description', 'LIKE', '%'.Request()->keyword.'%');
            } 
            $data->orderBy('isactive', 'desc')->orderBy('id', 'asc');
            $page  = Request()->per_page ?? '15';
            return response()->json($data->paginate($page), 200);
    
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) {
        DB::beginTransaction();
        try {
            mscHospitalRoomsStatus::updateOrCreate(
                [
                    'room_description'=>  $request->payload['room_description']
                ],
                [
                    'room_description' => $request->payload['room_description'] ?? '',
                    'isSystemDefault' => $request->payload['isSystemDefault'] ?? false, 
                    'isActive' => $request->payload['isActive'] ?? false, 
                    'created_at' => now(),
                ]
            );
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::beginTransaction();
        try {
            mscHospitalRoomsStatus::where('id', $id)->update([
                'room_description' => $request->payload['room_description'] ?? '',
                'isActive' => $request->payload['isActive'], 
                'isSystemDefault' => $request->payload['isSystemDefault'], 
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json(['msg'=>'success'], 200);

        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        DB::beginTransaction();
        try {
            mscHospitalRoomsStatus::where('id', $id)->delete();
            DB::commit();
            return response()->json(['msg'=>'success'], 200);
        } catch(\Exception $e) {
            DB::rollback();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
