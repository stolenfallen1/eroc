<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryExamsView extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_laboratory';
    protected $table = 'CDG_LABORATORY.dbo.vwLaboratoryCurrentExams';
    protected $guarded = [];
}
