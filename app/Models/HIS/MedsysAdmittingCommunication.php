<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysAdmittingCommunication extends Model
{
    use HasFactory;
    
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'PATIENT_DATA.dbo.tbER_Admitting_Communication';
    protected $guarded = [];

    protected $primaryKey = 'HospNum';

    public $timestamps = false;
    public $incrementing = false;
}
