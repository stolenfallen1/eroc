<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drugadministration extends Model
{
    use HasFactory;
    protected $table = 'mscDrugadministrationsRoute';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
