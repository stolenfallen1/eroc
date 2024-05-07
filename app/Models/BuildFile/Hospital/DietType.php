<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietType extends Model
{
    use HasFactory;

    protected $table = 'mscDietType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
