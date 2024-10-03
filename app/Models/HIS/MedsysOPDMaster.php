<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysOPDMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbOutPatient';
    protected $guarded = [];

    public $timestamps = false;
}
