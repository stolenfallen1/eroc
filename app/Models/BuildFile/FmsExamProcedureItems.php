<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FmsExamProcedureItems extends Model
{
    use HasFactory;
    protected $table = 'fmsExamProcedureItems';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
