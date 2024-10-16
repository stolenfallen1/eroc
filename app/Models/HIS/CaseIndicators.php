<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAllergies;

class CaseIndicators extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'CDG_CORE.dbo.mscCaseIndicators';
    protected $guarded = [];

    public function allergies() {
        return $this->belongsTo(PatientAllergies::class, 'id','allergies_Id');
    }
}
