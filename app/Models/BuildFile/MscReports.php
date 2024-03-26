<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscReports extends Model
{
    use HasFactory;   
    protected $table = 'CDG_CORE.dbo.mscReports';
    protected $connection = "sqlsrv";
    protected $guarded =[];
}
