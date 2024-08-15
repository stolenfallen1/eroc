<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPhysicalAbdomen extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientPhysicalAbdomen';
    protected $guarded = [];
}
