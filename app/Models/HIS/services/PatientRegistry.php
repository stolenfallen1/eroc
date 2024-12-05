<?php

namespace App\Models\HIS\services;

use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\HospitalPatientCategories;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientAllergies;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientBadHabits;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\PatientPhysicalExamtionGeneralSurvey;
use App\Models\HIS\PatientPhysicalSkinExtremities;
use App\Models\HIS\PatientPhysicalAbdomen;
use App\Models\HIS\PatientPhysicalGUIE;
use App\Models\HIS\PatientDoctors;
use App\Models\HIS\PatientPertinentSignAndSymptoms;
use App\Models\HIS\PatientPhysicalExamtionChestLungs;
use App\Models\HIS\PatientCourseInTheWard;
use App\Models\HIS\PatientPhysicalExamtionHEENT;
use App\Models\HIS\PatientPhysicalNeuroExam;
use App\Models\HIS\PatientPhysicalExamtionCVS;
use App\Models\HIS\PatientOBGYNHistory;
use App\Models\HIS\PatientMedications;
use App\Models\HIS\PatientDischargeInstructions;
use App\Models\HIS\PatientGynecologicalConditions;
use App\Models\HIS\PatientAppointments;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\services\Patient;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\FMS\AccountType;
use App\Models\BuildFile\Hospital\Company;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\Hospital\TransactionType;
use App\Models\BuildFile\PriceGroup;
use App\Models\BuildFile\PriceScheme;
use App\Models\MMIS\inventory\InventoryTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRegistry extends Model
{
    use HasFactory;
    // protected $table = 'CDG_PATIENT_DATA.dbo.PatientRegistry';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientRegistry';
    protected $connection = "sqlsrv_patient_data";
    protected $guarded = [];

    // Relationships
    public function medsysErMaster() {
        return $this->belongsTo(MedsysPatientMaster::class, 'IDnum', 'case_No');
    }
    public function patient_details(){
        return $this->belongsTo(Patient::class, 'patient_Id', 'patient_Id');
    }    
    public function branch() {
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }
    public function lab_services() {
        return $this->hasMany(LaboratoryMaster::class, 'case_No', 'case_No');
    }
    public function inventoryTransactions() {
        return $this->hasMany(InventoryTransaction::class, 'patient_Registry_Id', 'case_No');
    }
    public function nurse_logbook() {
        return $this->hasMany(NurseLogBook::class, 'case_No', 'case_No');
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
        return $this->hasOne(PatientHistory::class, 'case_No', 'case_No');
    }
    public function administered_medicines() {
        return $this->belongsTo(PatientAdministeredMedicines::class, 'case_No', 'case_No');
    }
    public function bad_habits() {
        return $this->belongsTo(PatientBadHabits::class,'case_No', 'case_No');
    }

    public function allergies() {
        return $this->hasMany(PatientAllergies::class,'case_No', 'case_No');
    }

    public function PhysicalExamtionGeneralSurvey() {
        return $this->belongsTo(PatientPhysicalExamtionGeneralSurvey::class, 'case_No', 'case_No');
    }

    public function physicalSkinExtremities() {
        return $this->belongsTo(PatientPhysicalSkinExtremities::class, 'case_No', 'case_No');
    }

    public function physicalAbdomen() {
        return $this->belongsTo(PatientPhysicalAbdomen::class, 'case_No', 'case_No');
    }

    public function physicalGUIE() {
        return $this->belongsTo(PatientPhysicalGUIE::class,'case_No','case_No');
    }

    public function patientDoctors() {
        return $this->hasOne(PatientDoctors::class,'case_No','case_No');
    }

    public function pertinentSignAndSymptoms() {
        return $this->belongsTo(PatientPertinentSignAndSymptoms::class, 'case_No', 'case_No');
    }
    public function physicalExamtionChestLungs() {
        return $this->belongsTo(PatientPhysicalExamtionChestLungs::class,'case_No','case_No');
    }
    public function courseInTheWard() {
        return $this->belongsTo(PatientCourseInTheWard::class, 'case_No', 'case_No');
    }

    public function physicalExamtionHEENT() {
        return $this->belongsTo(PatientPhysicalExamtionHEENT::class, 'case_No', 'case_No');
    }
    public function physicalNeuroExam() {
        return $this->belongsTo(PatientPhysicalNeuroExam::class,'case_No','case_No');
    }
    
    public function physicalExamtionCVS() {
        return $this->belongsTo(PatientPhysicalExamtionCVS::class, 'case_No', 'case_No');
    }
    public function oBGYNHistory() {
        return $this->belongsTo(PatientOBGYNHistory::class, 'case_No','case_No');
    }
    public function medications() {
        return $this->belongsTo(PatientMedications::class, 'case_No','case_No');
    }
    public function dischargeInstructions() {
        return $this->hasOne(PatientDischargeInstructions::class, 'case_No', 'case_No');
    }

    public function appointments() {
        return $this->belongsTo(PatientAppointments::class, 'case_No','case_No');
    }

}
