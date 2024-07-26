<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSpecimenLaboratory extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscExamSpecimenLaboratory';
    protected $guarded = [];
    public $timestamps = false;
    public function specimens() {
        return $this->belongsTo(ExamSpecimens::class, 'specimen_id', 'id');
    }
}

