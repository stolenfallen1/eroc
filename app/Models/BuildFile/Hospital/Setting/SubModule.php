<?php

namespace App\Models\BuildFile\Hospital\Setting;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Setting\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubModule extends Model
{
    use HasFactory;
    protected $table = 'sysSubModule';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    protected $with = ['modules'];
    public function modules()
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }
}
