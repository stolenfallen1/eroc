<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadHabits extends Model
{
    use HasFactory;

    protected $table = 'mscBadHabits';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
