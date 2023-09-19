<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionSource extends Model
{
    use HasFactory;
    protected $table = 'mscAdmissionSource';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
