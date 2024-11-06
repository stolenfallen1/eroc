<?php

namespace App\Models\HIS\his_functions;

use App\Models\HIS\mscDosages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseLogBook extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.NurseLogBook';
    protected $guarded = [];
    public $timestamps = false;

    public function dosage() {
        return $this->belongsTo(mscDosages::class, 'dosage', 'dosage_id');
    }    
}
