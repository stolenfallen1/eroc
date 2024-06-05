<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\GetIP;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use TCG\Voyager\Facades\Voyager;
use App\Models\POS\POSBIRSettings;
use Illuminate\Support\Facades\Auth;
use App\Helpers\HIS\SysGlobalSetting;
use Illuminate\Support\Facades\Storage;
use App\Models\BuildFile\SystemSequence;
use App\Helpers\PosSearchFilter\Terminal;

class AuthPOSController extends \TCG\Voyager\Http\Controllers\Controller
{
    protected $shift;

    public function login(Request $request)
    {
        $credentials = $request->only('idnumber', 'password');

        if ($request->role == 2 && Payments::where('sales_invoice_number', $request->sequenceno)->exists()) {
            return response()->json(["message" => 'Sequence number already used!'], 401);
        }

        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
            $ipaddress = $request['clientIP'] ? $request['clientIP'] : (new GetIP())->value();
            if ((new Terminal())->check_terminal($ipaddress) == 0) {
                return response()->json(["message" => 'You are not allowed to access'], 401);
            }

            $shift = Auth()->user()->shift;
            $shift = $request->shift != 0 ? $request->shift : $shift;

            $user = Auth::user();
            if ($user->isactive == 1) {
                $updateData = [
                    'user_ipaddress' => $ipaddress,
                    'terminal_id' => (new Terminal())->terminal_details()->id,
                    'shift' => $shift,
                ];

                User::where('id', $user->id)->update($updateData);
                // $token = $user->createToken();
                // $user->load('role.permissions', 'roles');
                if($user->isactive == 1) {
                    $token = $user->createToken();
                    // $user->load('role.permissions', 'roles');
                    return response()->json(['user' => $user, 'access_token' => $token], 200);
                }
            }
        }

        return response()->json(["message" => 'Warning: Enter a valid id number and password before proceeding!'], 401);
    }

    public function refreshToken(Request $request)
    {
        $accessToken = auth()->user()->token();
        $accessToken->revoke();

        $newAccessToken = $request->user()->createToken();

        return response()->json(['access_token' => $newAccessToken]);
    }

    public function checkTerminal()
    {
        $termninal = '';
        $hostname = gethostname();
        $ipaddress = (new GetIP())->value();
        $checksystemtermnial = (new Terminal())->terminal_details();

        if (!$checksystemtermnial) {
            return false;
        }

        $termninal = $checksystemtermnial;
        return $termninal;
    }

    public function logout()
    {
        Auth::user()->revokeToken();
        Auth::guard('web')->logout();
        return response()->json(['message' => 'Success'], 200);
    }

    public function userDetails()
    {
        $modulelist = Voyager::model('MenuItem')->whereNull('parent_id')->where('menu_id', '1')->orderBy('order', 'asc')->get();
        $modulelist->filter(function ($item) {
            // check if action
            return !$item->children->isEmpty() || Auth::user()->can('browse', $item);

        })->filter(function ($item) {
            // Filter out empty menu-items
            if ($item->url == '' && $item->route == '' && $item->children->count() == 0) {
                return false;
            }
            return true;
        });
        if (!$this->checkTerminal()) {
            return response()->json(["message" => 'You are not allowed to access'], 403);
        }

        $user = Auth::user();
        $user->pos_setting = POSSettings::where('isActive', '1')->first();
        $user->isposetting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
        $user->sysTerminal = $this->checkTerminal();
        $user->check_is_allow_medsys_status = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $user->serverIP =  config('app.pos_server_ip');
        $data['details'] = $user;

        return response()->json(['user' => $data], 200);
    }

    public function systemsubcomponents()
    {
        $menuid = '1';
        $module_id = '124';

        $items = Voyager::model('MenuItem')->with('childrensub')->where('menu_id', $menuid)->where('parent_id', $module_id)->get();

        $data['submodule'] = $items->filter(function ($item) {
            return !$item->children->isEmpty() || Auth::user()->can('browse', $item);
        })->filter(function ($item) {
            if ($item->url == '' && $item->route == '' && $item->children->count() == 0) {
                return false;
            }
            return true;
        });

        return $data;
    }

    public function verifyPasscode(Request $request)
    {
        if (Auth::user()->passcode === $request->code) {
            return response()->json(["message" => 'Success'], 200);
        }

        return response()->json(["message" => 'Incorrect passcode'], 403);
    }

    protected function guard()
    {
        return Auth::guard('web');
    }
}
