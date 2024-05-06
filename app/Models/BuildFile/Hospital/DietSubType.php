<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DietSubType extends Model
{
    use HasFactory;

    protected $table = 'mscDietSubType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
