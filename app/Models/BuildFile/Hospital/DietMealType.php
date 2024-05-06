<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietMealType extends Model
{
    use HasFactory;

    protected $table = 'mscDietmealType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
