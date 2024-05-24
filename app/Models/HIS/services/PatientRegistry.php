<?php

namespace App\Models\HIS\services;

use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\FMS\AccountType;
use App\Models\BuildFile\Hospital\Company;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\TransactionType;
use App\Models\BuildFile\PriceGroup;
use App\Models\BuildFile\PriceScheme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientRegistry extends Model
{
    use HasFactory;
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientRegistry';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    // Relationships
    public function branch() {
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }
    public function accountType() {
        return $this->belongsTo(AccountType::class, 'mscAccount_type', 'id');
    }
    public function transactionType() {
        return $this->belongsTo(TransactionType::class, 'mscAccount_trans_types', 'id' );
    }
    public function priceGroups() {
        return $this->belongsTo(PriceGroup::class, 'mscPrice_Groups', 'id');
    }
    public function priceSchemes() {
        return $this->belongsTo(PriceScheme::class, 'mscPrice_Schemes', 'id');
    }
    public function guarantor() {
        return $this->belongsTo(Company::class, 'guarantor_id', 'id');
    }
    public function doctor() {
        return $this->belongsTo(Doctor::class, 'attending_doctor', 'id');
    }
}
