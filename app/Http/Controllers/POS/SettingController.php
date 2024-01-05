<?php

namespace App\Http\Controllers\POS;

use App\Helpers\GetIP;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Systerminals;
use App\Models\BuildFile\mscShiftSchedules;
use DB;
use Carbon\Carbon;
class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $schedule = DB::connection('sqlsrv_pos')->table('vwShift')->where('isActive','1')->select('shifts_code','Shift_description','beginning_military_hour')->get();
        $all = Array('shifts_code'=>'0','Shift_description'=>'All Shift','beginning_military_hour'=>'0');
        $array = [];
        $array[] = $all;
        foreach($schedule as $row){
            $array[] = $row;
        }
        $data['schedule'] = $array;
        $data['ip'] = (new GetIP)->value();
        $data['terminal'] = Systerminals::where('terminal_ip_address',(new GetIP)->value())->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->first();
        return response()->json($data,200);
    }

    
    public function getLanIpAddress()
    {
            // Execute a shell command to get the LAN IP address
            $output = shell_exec("/usr/sbin/ifconfig");

            // Use regular expression to extract the LAN IP address
            preg_match("/inet addr:(\d+\.\d+\.\d+\.\d+)/", $output, $matches);

            // Return the LAN IP address if found, otherwise return false
            return isset($matches[1]) ? $matches[1] : false;
    }

    function getwindowLanIpAddress()
     {
        // Execute a shell command to get the LAN IP address using ipconfig
        $output = shell_exec("ipconfig");

        // Use regular expression to extract the LAN IP address
        preg_match("/IPv4 Address[^\d]+(\d+\.\d+\.\d+\.\d+)/", $output, $matches);

        // Return the LAN IP address if found, otherwise return false
        return isset($matches[1]) ? $matches[1] : false;
    }

    public function schedule()
    {
        $schedule = DB::connection('sqlsrv_pos')->table('vwShift')->where('isActive','1')->select('shifts_code','Shift_description','beginning_military_hour')->get();
        $all = Array('shifts_code'=>'0','Shift_description'=>'All Shift','beginning_military_hour'=>'0');
        $array = [];
        $array[] = $all;
        foreach($schedule as $row){
            $array[] = $row;
        }
        
        $localIp = getHostByName(Request()->server('REMOTE_ADDR'));
        $data['schedule'] = $array;
        $data['remote_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['clientip'] =Request()->getClientIp();
        $data['localIP'] = $localIp;
        $data['getHostName'] = getHostName();
        $data['lanIpAddress '] = $this->getLanIpAddress();
        $data['lanwindowIpAddress '] = $this->getwindowLanIpAddress();
                

        $data['ip'] = (new GetIP())->value();
        $data['terminal'] = Systerminals::where('terminal_ip_address',(new GetIP)->value())->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->first();
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
        //
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
        //
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
