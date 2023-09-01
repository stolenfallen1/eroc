<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\Approver\InvApproverLevel;
use Illuminate\Http\Request;

class ApproverLevelController extends Controller
{
    public function index()
    {
        $data = InvApproverLevel::query();
        if(Request()->keyword) {
            $data->where('level_description', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }
    public function store(Request $request)
    {
        $check_if_exist = InvApproverLevel::select('level_description')->where('level_description', $request->payload['level_description'])->first();
        if(!$check_if_exist) {
            $data['data'] = InvApproverLevel::create([
                'level_description' => $request->payload['level_description'],
                'isActive' => $request->payload['isActive'],
            ]);
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        }
        $data['msg'] = 'Already Exists!';
        return Response()->json($data, 200);

    }

    public function update(Request $request, $id)
    {
        $data['data'] = InvApproverLevel::where('id', $id)->update([
                'level_description' => $request->payload['level_description'],
                'isActive' => $request->payload['isActive'],
             ]);

        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
    public function destroy($id)
    {
        try {
            $data['data'] = InvApproverLevel::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
