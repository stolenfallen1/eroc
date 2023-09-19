<?php

namespace App\Models\BuildFile\Hospital\Setting;

use App\Models\GlobalSettings;
use App\Models\SystemReports;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    use HasFactory;
    protected $table = 'sysSystem';
    protected $connection = "sqlsrv";
    protected $guarded = [];


    public function globalSettings()
    {
        return $this->hasMany(GlobalSettings::class, 'Systems_id', 'id');
    }
    public function modules()
    {
        return $this->hasMany(Module::class, 'system_id', 'id')->whereNotIn('database_driver',['sqlsrv']);
    }

    public function reports()
    {
        return $this->hasMany(SystemReports::class, 'system_id', 'id');
    }
}
