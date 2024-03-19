<?php

namespace App\Http\Controllers\HIS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HIS\SysConfigGeneral;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function store(Request $request){
        $input = $request->except(['updated_at', 'created_at']);
        SysConfigGeneral::updateOrCreate([
            'branch_id' => Auth::user()->branch_id
        ], $input);
        return 'success';
    }

    public function update(Request $request, $id){
        $input = $request->except(['updated_at', 'created_at']);
        SysConfigGeneral::where('id', $id)->update($input);
        return 'success';
    }
}
