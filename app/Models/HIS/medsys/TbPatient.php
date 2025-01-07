<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TbPatient extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbPatient';
    protected $guarded = [];
    public $timestamps = false;
}
