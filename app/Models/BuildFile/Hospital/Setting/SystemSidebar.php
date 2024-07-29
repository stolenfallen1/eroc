<?php

namespace App\Models\BuildFile\Hospital\Setting;

use App\Models\SystemReports;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Setting\System;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSidebar extends Model
{
    use HasFactory;
    protected $table = 'sidebar_group';
    protected $connection = "sqlsrv";
    protected $guarded = [];
   
}
