<?php

namespace App\Models\HIS\his_functions;

use App\Models\HIS\services\PatientRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratoryMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_laboratory';
    protected $table = 'CDG_LABORATORY.dbo.LaboratoryMaster';
    protected $guarded = [];
    public $timestamps = false;

    public function patientRegistry() {
        return $this->hasMany(PatientRegistry::class, 'case_No', 'case_No');
    }
    public function billingOut() {
        return $this->belongsTo(HISBillingOut::class, 'profileId', 'itemID');
    }
}