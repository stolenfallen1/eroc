<?php

namespace App\Models\HIS\SOA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\SOA\OPDBilling;

class OutPatient extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'CDG_BILLING.dbo.vwOutPatient';
    protected $guarded = [];

    public function patientBillingInfo() {
        return $this->hasMany(OPDBilling::class, 'patient_Id', 'patient_Id');
                    
    }
}
