<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionType extends Model
{
    use HasFactory;
    protected $table = 'mscAdmissionType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
