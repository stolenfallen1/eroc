<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysPatientMasterDetails extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbmaster2';
    protected $guarded = [];
    public $timestamps = false;
}
