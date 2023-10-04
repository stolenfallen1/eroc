<?php

namespace App\Models;

use App\Models\BuildFile\Hospital\Setting\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
