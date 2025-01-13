<?php

namespace App\Models\BuildFile;

use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\PatientAppointment;
use App\Models\HIS\his_functions\ExamProcedureSections;
use App\Models\HIS\his_functions\ExamSpecimenLaboratory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\mscHospitalExamItemCategory;

class FmsExamProcedureItems extends Model
{
    use HasFactory;
    protected $table = 'fmsExamProcedureItems';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    public function appointments() {
        return $this->hasMany(PatientAppointment::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
    

    public function category(){
        return $this->belongsTo(mscHospitalExamItemCategory::class, 'msc_item_category_ID', 'id');
    }

    public function prices(){
        return $this->hasMany(FmsExamProcedureItemsPrice::class, 'examprocedure_id','id');
    }

    // FOR HIS
    public function sections() {
        return $this->belongsTo(ExamProcedureSections::class, 'exam_section', 'map_sections_id');
    }
    public function procedures() {
        return $this->belongsTo(AppointmentCenter::class, 'revenieID', 'map_sections_id');
    }
    public function specimens() {
        return $this->belongsTo(ExamSpecimenLaboratory::class, 'map_item_id', 'exam_id');
    }
}
