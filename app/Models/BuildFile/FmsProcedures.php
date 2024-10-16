<?php

namespace App\Models\BuildFile;

use App\Models\HIS\his_functions\ExamProcedureSections;
use App\Models\HIS\his_functions\ExamSpecimenLaboratory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\mscHospitalExamItemCategory;

class FmsProcedures extends Model
{
    use HasFactory;
    protected $table = 'vwProcedures';
    protected $connection = "sqlsrv";
    protected $guarded = [];

}
