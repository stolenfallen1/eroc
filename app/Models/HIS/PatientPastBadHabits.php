<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPastBadHabits extends Model
{
    use HasFactory;
    protected $connecttion = 'sqlsrv_patient_data';
    protected $table = 'CDG.PATIENT_DATA.dbo.PatientPastBadHabits';
    protected $guarded = [];
}
