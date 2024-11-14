<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmittingCommunicationFile extends Model
{
    use HasFactory;
    protected $communication = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.AdmittingCommunicationFile';
    protected $guarded = [];

    public $timestamps = false;
}
