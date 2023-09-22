<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSettings extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysGlobalSettings';
    protected $guarded = [];
    protected $with = ['reports'];
   
    public function reports()
    {
        return $this->hasMany(SystemReports::class, 'system_id', 'Systems_id');
    }
}
