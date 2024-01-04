<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\TerminalTakeOrder;
use App\Models\BuildFile\vwTerminalTakeOrder;

class TakeOrderTerminalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     
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
            TerminalTakeOrder::create([
                'branch_Id'=>$request->payload_terminal['branch_Id'] ?? '',
                'warehouse_Id'=>$request->payload_terminal['warehouse_Id'] ?? '',
                'terminal_Id'=>$request->payload_terminal['id'] ?? '',
                'terminal_takeorder_code'=>$request->payload_takeorder_terminal['terminal_code'] ?? '',
                'terminal_takeorder_name'=>$request->payload_takeorder_terminal['terminal_name'] ?? '',
                'terminal_takeorder_brand'=>$request->payload_takeorder_terminal['terminal_brand'] ?? '',
                'terminal_takeorder_serial_number'=>$request->payload_takeorder_terminal['terminal_serial_number'] ?? '',
                'terminal_takeorder_Machine_Identification_Number'=>$request->payload_takeorder_terminal['terminal_Machine_Identification_Number'] ?? '',
                'terminal_takeorder_ip_address'=>$request->payload_takeorder_terminal['terminal_ip_address'] ?? '',
                'terminal_takeorder_mac_address'=>$request->payload_takeorder_terminal['terminal_mac_address'] ?? '',
                'isActive'=>$request->payload_takeorder_terminal['isActive'] ?? '',
                'isitem_Selling_Price_Out'=>$request->payload_takeorder_terminal['isitem_Selling_Price_Out'] ?? '',
                'isitem_Selling_Price_In'=>$request->payload_takeorder_terminal['isitem_Selling_Price_In'] ?? '',
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
        $data['takeorderterminal'] = vwTerminalTakeOrder::where('terminal_id',$id)->get();
        return response()->json($data,200);
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
            TerminalTakeOrder::where('id',$id)->update([
                'branch_Id'=>$request->payload_terminal['branch_Id'] ?? '',
                'warehouse_Id'=>$request->payload_terminal['warehouse_Id'] ?? '',
                'terminal_Id'=>$request->payload_terminal['id'] ?? '',
                'terminal_takeorder_code'=>$request->payload_takeorder_terminal['terminal_code'] ?? '',
                'terminal_takeorder_name'=>$request->payload_takeorder_terminal['terminal_name'] ?? '',
                'terminal_takeorder_brand'=>$request->payload_takeorder_terminal['terminal_brand'] ?? '',
                'terminal_takeorder_serial_number'=>$request->payload_takeorder_terminal['terminal_serial_number'] ?? '',
                'terminal_takeorder_Machine_Identification_Number'=>$request->payload_takeorder_terminal['terminal_Machine_Identification_Number'] ?? '',
                'terminal_takeorder_ip_address'=>$request->payload_takeorder_terminal['terminal_ip_address'] ?? '',
                'terminal_takeorder_mac_address'=>$request->payload_takeorder_terminal['terminal_mac_address'] ?? '',
                'isActive'=>$request->payload_takeorder_terminal['isActive'] ?? '',
                'isitem_Selling_Price_Out'=>$request->payload_takeorder_terminal['isitem_Selling_Price_Out'] ?? '',
                'isitem_Selling_Price_In'=>$request->payload_takeorder_terminal['isitem_Selling_Price_In'] ?? '',
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
       
        $details = TerminalTakeOrder::find($id);
        $details->delete();
        return response()->json(["message" =>  'Record successfully deleted','status' => '200'], 200);
    }
}
