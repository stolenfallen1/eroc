<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalPackageMaster extends Model
{
    use HasFactory;
    protected $table = 'mscMedicalPackageMaster';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
