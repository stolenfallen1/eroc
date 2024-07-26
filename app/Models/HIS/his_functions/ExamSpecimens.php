<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSpecimens extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscExamSpecimens';
    protected $guarded = [];
    public $timestamps = false;
    public function specimens() {
        return $this->belongsTo(ExamSpecimenLaboratory::class, 'id', 'specimen_id');
    }
}
