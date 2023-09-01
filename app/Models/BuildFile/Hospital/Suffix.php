<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suffix extends Model
{
    use HasFactory;
    protected $table = 'mscSuffix';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
