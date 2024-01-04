<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Systerminals;

class TerminalSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $data = Systerminals::query();
            $data->with("takeOrders");
            if(Request()->keyword) {
                $data->where('terminal_name', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
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
        DB::connection('sqlsrv_pos')->beginTransaction();
        try{
            Systerminals::create([
                'terminal_code'=>$request->payload['terminal_code'] ?? '',
                'terminal_name'=>$request->payload['terminal_name'] ?? '',
                'terminal_brand'=>$request->payload['terminal_brand'] ?? '',
                'terminal_serial_number'=>$request->payload['terminal_serial_number'] ?? '',
                'terminal_Machine_Identification_Number'=>$request->payload['terminal_Machine_Identification_Number'] ?? '',
                'terminal_ip_address'=>$request->payload['terminal_ip_address'] ?? '',
                'terminal_mac_address'=>$request->payload['terminal_mac_address'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
                'isitem_Selling_Price_Out'=>$request->payload['isitem_Selling_Price_Out'] ?? '',
                'isitem_Selling_Price_In'=>$request->payload['isitem_Selling_Price_In'] ?? '',
            ]);
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
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
        DB::connection('sqlsrv_pos')->beginTransaction();
        try{
            Systerminals::where('id',$id)->update([
                'terminal_code'=>$request->payload['terminal_code'] ?? '',
                'terminal_name'=>$request->payload['terminal_name'] ?? '',
                'terminal_brand'=>$request->payload['terminal_brand'] ?? '',
                'terminal_serial_number'=>$request->payload['terminal_serial_number'] ?? '',
                'terminal_Machine_Identification_Number'=>$request->payload['terminal_Machine_Identification_Number'] ?? '',
                'terminal_ip_address'=>$request->payload['terminal_ip_address'] ?? '',
                'terminal_mac_address'=>$request->payload['terminal_mac_address'] ?? '',
                'isActive'=>$request->payload['isActive'] ?? '',
                'isitem_Selling_Price_Out'=>$request->payload['isitem_Selling_Price_Out'] ?? '',
                'isitem_Selling_Price_In'=>$request->payload['isitem_Selling_Price_In'] ?? '',
            ]);
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
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
        $details = Systerminals::find($id);
        $details->delete();
        return response()->json(["message" =>  'Record successfully deleted','status' => '200'], 200);
    }
}