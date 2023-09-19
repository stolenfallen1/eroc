<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysSeriesNo extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbAdmLastNumber';
    protected $guarded = [];
    public $timestamps = false;
}
