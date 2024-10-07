<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use Illuminate\Database\Eloquent\Model;

class ExamLaboratoryProfileExams extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_laboratory';
    protected $table = 'CDG_CORE.dbo.mscExamLaboratoryProfileExams';
    protected $guarded = [];
    public $timestamps = false;

    public function lab_profile () {
        return $this->belongsTo(ExamLaboratoryProfiles::class, 'map_profile_id', 'map_profile_id');
    }
} 
