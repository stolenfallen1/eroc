<?php

namespace App\Http\Controllers;

use App\Models\BuildFile\GlobalSetting;
use App\Models\BuildFile\Hospital\Setting\System;
use App\Models\UserGlobalAccess;
use Illuminate\Http\Request;

class GlobalSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $data = System::with('globalSettings')->get();
        return response()->json($data,200);
    }
    public function getuseraccess(Request $request)
    {
       $data = UserGlobalAccess::where('user_id', $request->idnumber)->get();
        return response()->json($data,200);
    }

    public function add_user_access(Request $request)
    {
        $data = UserGlobalAccess::create([
            'user_id'=>$request->idnumber,
            'globalsetting_id'=>$request->globalsetting_id,
        ]);
        return response()->json($data,200);
    }

     public function remove_user_access(Request $request)
    {
        UserGlobalAccess::where('user_id',$request->idnumber)->where('globalsetting_id',$request->globalsetting_id)->delete();
        return response()->json(['msg'=>'deleted'],200);
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
