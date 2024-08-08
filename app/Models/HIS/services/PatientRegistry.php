<?php

namespace App\Models\HIS\services;

use App\Models\HIS\his_functions\HospitalPatientCategories;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientPastImmunizations;
use App\Models\HIS\PatientPastMedicalHistory;
use App\Models\HIS\PatientPastMedicalProcedures;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\services\Patient;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\FMS\AccountType;
use App\Models\BuildFile\Hospital\Company;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\TransactionType;
use App\Models\BuildFile\PriceGroup;
use App\Models\BuildFile\PriceScheme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRegistry extends Model
{
    use HasFactory;
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientRegistry';
    protected $connection = "sqlsrv_patient_data";
    protected $guarded = [];

    // Relationships
    public function patient_details(){
        return $this->belongsTo(Patient::class, 'patient_Id', 'patient_id');
    }    
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
    public function patientCategory() {
        return $this->belongsTo(HospitalPatientCategories::class, 'mscPatient_Category', 'id');
    }
    public function vitals() {
        return $this->hasOne(PatientVitalSigns::class, 'case_No', 'case_No');
    }
    public function medical_procedures() {
        return $this->belongsTo(PatientMedicalProcedures::class, 'case_No', 'case_No');
    }
    public function immunizations() {
        return $this->belongsTo(PatientImmunizations::class, 'case_No', 'case_No');
    }
    public function history() {
        return $this->belongsTo(PatientHistory::class, 'case_No', 'case_No');
    }
    public function administered_medicines() {
        return $this->belongsTo(PatientAdministeredMedicines::class, 'case_No', 'case_No');
    }
}
