<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
    use HasFactory;
    protected $table = 'mscBanks';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
