<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_laboratory';
    protected $table = 'LaboratoryMaster';
    protected $guarded = [];
    public $timestamps = false;
}