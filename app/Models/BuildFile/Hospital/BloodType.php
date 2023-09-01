<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodType extends Model
{
    use HasFactory;
    protected $table = 'mscBloodTypes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
