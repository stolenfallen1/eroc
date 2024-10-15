<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscReports extends Model
{
    use HasFactory;   
    protected $table = 'mscReports';
    protected $connection = "sqlsrv";
    protected $guarded =[];
}
