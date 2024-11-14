<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbNurseCommunicationFile extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_nurse_station';
    protected $primaryKey = 'RequestNum';
    protected $table = 'STATION.dbo.tbNurseCommunicationFile';
    protected $guarded = [];
    public $timestamps = false;
}
