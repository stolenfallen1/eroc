<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\MedsysPatientMaster;
class MedsysPatientMaster2 extends Model
{
    use HasFactory;

    protected $table = 'PATIENT_DATA.dbo.tbmaster2';
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $primaryKey = 'HospNum';
    protected $guarded = [];
    public $timestamps = false;
}
