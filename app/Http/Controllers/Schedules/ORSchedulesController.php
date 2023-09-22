<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\Schedules\ORSchedulesModel;
use Illuminate\Http\Request;

class ORSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data =  ORSchedulesModel::get();
        return response()->json($data, 200);
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
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function show(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function edit(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }
}
