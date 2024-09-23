<?php

namespace App\Models\HIS\SOA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\SOA\OutPatient;

class OPDBilling extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'vwOPDBilling';
    protected $guarded = [];

    public function outPatientInfo() {
        return $this->hasMany(OutPatient::class,  'patient_Id', 'patient_Id')->orderBy('revenueID', 'asc');
    }
    
}
