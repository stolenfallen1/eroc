<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamProcedureSections extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscExamProcedureSections';
    protected $guarded = [];
    public $timestamps = false;

}
