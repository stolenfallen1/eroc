<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\his_functions\ExamLaboratoryProfileExams;
use Illuminate\Database\Eloquent\Model;

class ExamLaboratoryProfiles extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_laboratory';
    protected $table = 'CDG_CORE.dbo.mscExamLaboratoryProfiles';
    protected $guarded = [];
    public $timestamps = false;
    
    public function lab_exams () {
        return $this->hasMany(ExamLaboratoryProfileExams::class, 'map_profile_id', 'map_profile_id');
    }
}
