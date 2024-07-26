<?php

namespace App\Models\BuildFile\Hospital\Setting;

use App\Models\SystemReports;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Setting\System;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\Setting\SystemSidebar;

class Module extends Model
{
    use HasFactory;
    protected $table = 'sysModules';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    protected $with = ['systems','reports','sidebar'];
    
    public function systems()
    {
        return $this->belongsTo(System::class, 'system_id', 'id');
    }

    public function reports()
    {
        return $this->hasMany(SystemReports::class, 'module_id', 'id');
    }

    public function sidebar()
    {
        return $this->belongsTo(SystemSidebar::class, 'sidebar_group_id', 'id');
    }

}
