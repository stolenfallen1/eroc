<?php

namespace App\Models;

use App\Models\BuildFile\MscReports;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Setting\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemReports extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysreports';
    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }
    public function reports()
    {
        return $this->belongsTo(MscReports::class, 'mscreport_id', 'id');
    }
    
}
