<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\services\Patient;
use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientHistory';
    protected $guarded = [];
    // protected $with = [''];
}
