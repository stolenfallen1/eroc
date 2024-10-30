<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbNurseLogBook extends Model
{
    protected $connection = 'sqlsrv_medsys_nurse_station';
    protected $primaryKey = 'RequestNum';
    protected $table = 'STATION.dbo.tbNurseLogBook';
    protected $guarded = [];
    public $timestamps = false;
    
}
