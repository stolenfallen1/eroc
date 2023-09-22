<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Titles extends Model
{
    protected $table = 'mscTitles';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
