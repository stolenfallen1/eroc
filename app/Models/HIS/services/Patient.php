<?php

namespace App\Models\HIS\services;

use App\Models\BuildFile\address\Barangay;
use App\Models\BuildFile\address\Country;
use App\Models\BuildFile\address\Municipality;
use App\Models\BuildFile\address\Province;
use App\Models\BuildFile\address\Region;
use App\Models\BuildFile\address\Zipcode;
use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Hospital\BloodType;
use App\Models\BuildFile\Hospital\CivilStatus;
use App\Models\BuildFile\Hospital\DeathType;
use App\Models\BuildFile\Hospital\Nationalities;
use App\Models\BuildFile\Hospital\Religions;
use App\Models\BuildFile\Hospital\Sex;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\PatientAdministeredMedicines;
use App\Models\HIS\PatientMedications;
use App\Models\HIS\PatientPastBadHabits;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\PatientImmunizations;
use App\Models\HIS\PatientMedicalProcedures;
use App\Models\HIS\PatientDrugUsedForAllergy;
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
use App\Models\HIS\PatientDischargeInstructions;
use App\Models\HIS\PatientGynecologicalConditions;
use App\Models\HIS\PatientPastAllergyHistory;
use App\Models\HIS\PatientPastImmunizations;
use App\Models\HIS\PatientPastMedicalHistory;
use App\Models\HIS\PatientPastMedicalProcedures;
use App\Models\HIS\PatientPrivilegedCard;
use App\Models\HIS\PatientAppointments;
use App\Models\HIS\PatientVitalSigns;
use App\Models\HIS\PatientAllergies;
use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory;
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientMaster';
    protected $connection = "sqlsrv_patient_data";
    protected $guarded = [];
    protected $appends = ['name'];

    public function getNameAttribute(){
        return $this->lastname.', '.$this->firstname.' '.$this->middlename;
    }
    // Relationships
    public function medsysPatientInfo() {
        return $this->belongsTo(MedsysPatientMaster::class, 'HospNum', 'patient_Id');
    }
    public function patientRegistry() {
        return $this->hasMany(PatientRegistry::class, 'patient_Id', 'patient_Id');
    }

    public function billingOut() {
        return $this->hasMany(HISBillingOut::class, 'patient_Id', 'patient_Id');
    }
    public function sex() {
        return $this->belongsTo(Sex::class, 'sex_id', 'id');
    }
    public function nationality() {
        return $this->belongsTo(Nationalities::class, 'nationality_id' , 'id');
    }
    public function religion() {
        return $this->belongsTo(Religions::class, 'religion_id', 'id');
    }
    public function civilStatus() {
        return $this->belongsTo(CivilStatus::class, 'civilstatus_id', 'id');
    }
    public function deathType() {
        return $this->belongsTo(DeathType::class, 'typeofdeath_id', 'id');
    }
    public function bloodType() {
        return $this->belongsTo(BloodType::class, 'bloodtype_id', 'id');
    }
    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }
    public function provinces() {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }
    public function municipality() {
        return $this->belongsTo(Municipality::class, 'municipality_id', 'id');
    }
    public function barangay() {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'id');
    }
    public function zipcode() {
        return $this->belongsTo(Zipcode::class, 'zipcode_id', 'id');
    }
    public function countries() {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function branch() {
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }
    public function past_immunization() {
        return $this->hasMany(PatientPastImmunizations::class, 'patient_Id', 'patient_Id');
    }
    public function past_medical_history() {
        return $this->hasMany(PatientPastMedicalHistory::class, 'patient_Id', 'patient_Id');
    }
    public function past_medical_procedures() {
        return $this->hasMany(PatientPastMedicalProcedures::class, 'patient_Id', 'patient_Id');
    }

    public function past_bad_habits() {
        return $this->hasMany(PatientPastBadHabits::class, 'patient_Id', 'patient_Id');
    }

    public function allergies() {
        return $this->belongsTo(PatientAllergies::class,'patient_Id', 'patient_Id');
    }

    public function drug_used_for_allergy() {
        return $this->hasMany(PatientDrugUsedForAllergy::class,'patient_Id', 'patient_Id');
    }

    public function physicalExamtionGeneralSurvey() {
        return $this->hasMany(PatientPhysicalExamtionGeneralSurvey::class,'patient_Id','patient_Id');
    }

    public function physicalSkinExtremities() {
        return $this->hasMany(PatientPhysicalSkinExtremities::class, 'patient_Id', 'patient_Id');
    }

    public function physicalAbdomen() {
        return $this->hasMany(PatientPhysicalAbdomen::class, 'patient_Id','patient_Id');
    }

    public function physicalGUIE() {
        return $this->hasMany(PatientPhysicalGUIE::class,'patient-Id','patient_Id');
    }

    public function patientDoctors() {
        return $this->hasMany(PatientDoctors::class,'patient_Id','patient_Id');
    }

    public function pertinentSignAndSymptoms() {
        return $this->hasMany(PatientPertinentSignAndSymptoms::class, 'patient_Id', 'patient_Id');
    }

    public function physicalExamtionChestLungs() {
        return $this->hasMany(PatientPhysicalExamtionChestLungs::class,'patient_Id','patient_Id');
    }

    public function courseInTheWard() {
        return $this->hasMany(PatientCourseInTheWard::class, 'patient_Id', 'patient_Id');
    }

    public function physicalExamtionHEENT() {
        return $this->hasMany(PatientPhysicalExamtionHEENT::class, 'patient_Id', 'patient_Id');
    }

    public function physicalNeuroExam() {
        return $this->hasMany(PatientPhysicalNeuroExam::class,'patient_Id','patient_Id');
    }

    public function physicalExamtionCVS() {
        return $this->hasMany(PatientPhysicalExamtionCVS::class, 'patient_Id', 'patient_Id');
    }

    public function oBGYNHistory() {
        return $this->hasMany(PatientOBGYNHistory::class,'patient_Id','patient_Id');
    }

    public function medications() {
        return $this->hasMany(PatientMedications::class, 'patient_Id','patient_Id');
    }

    public function dischargeInstructions() {
        return $this->hasMany(PatientDischargeInstructions::class, 'patient_Id', 'patient_Id');
    }

    public function privilegedCard() {
        return $this->hasMany(PatientPrivilegedCard::class, 'patient_Id', 'patient_Id');
    }

    public function appointments() {
        return $this->hasMany(PatientAppointments::class, 'patient_Id','patient_Id');
    }

}
