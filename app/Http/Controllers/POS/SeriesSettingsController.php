<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;

class SeriesSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['series'] = SystemSequence::where('subsystem_id',8)->whereNotNull('terminal_code')->get();
        return response()->json($data,200);
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
        DB::connection('sqlsrv')->beginTransaction();
        try{
            SystemSequence::create([
                'code'=>$request->payload['code'] ?? '',
                'digit'=>$request->payload['digit'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
                'isPos'=>$request->payload['isPos'] ?? '',
                'isSystem'=>$request->payload['isSystem'] ?? '',
                'manual_recent_generated'=>$request->payload['manual_recent_generated'] ?? '',
                'manual_seq_no'=>$request->payload['manual_seq_no'] ?? '',
                'seq_description'=>$request->payload['seq_description'] ?? '',
                'recent_generated'=>$request->payload['recent_generated'] ?? '',
                'seq_no'=>$request->payload['seq_no'] ?? '',
                'seq_prefix'=>$request->payload['seq_prefix'] ?? '',
                'seq_suffix'=>$request->payload['seq_suffix'] ?? '',
                'terminal_code'=>$request->payload['terminal_code'] ?? '',
                'subsystem_id'=>'8',
                'branch_id'=>'1',
                'system_id'=>'1',
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try{
            SystemSequence::where('id',$id)->update([
                'code'=>$request->payload['code'] ?? '',
                'digit'=>$request->payload['digit'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
                'isPos'=>$request->payload['isPos'] ?? '',
                'isSystem'=>$request->payload['isSystem'] ?? '',
                'manual_recent_generated'=>$request->payload['manual_recent_generated'] ?? '',
                'manual_seq_no'=>$request->payload['manual_seq_no'] ?? '',
                'seq_description'=>$request->payload['seq_description'] ?? '',
                'recent_generated'=>$request->payload['recent_generated'] ?? '',
                'seq_no'=>$request->payload['seq_no'] ?? '',
                'seq_prefix'=>$request->payload['seq_prefix'] ?? '',
                'seq_suffix'=>$request->payload['seq_suffix'] ?? '',
                'terminal_code'=>$request->payload['terminal_code'] ?? '',
                'subsystem_id'=>'8',
                'branch_id'=>'1',
                'system_id'=>'1',
            ]);
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
