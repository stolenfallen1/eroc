<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\DoctorCategories;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'hmsDoctors';
    // protected $fillable = ['lastname','firstname','middlename', 'doctor_code'];
    protected $appends = ['doctor_name'];
    protected $with = ["doctorCategory","doctorSpecialty"];
    protected $guarded = [];
    public function getDoctorNameAttribute()
    {
        return $this->lastname . ', ' .$this->firstname. ' ' .$this->middlename;
    }

    public function doctorCategory(){
        return $this->belongsTo(DoctorCategories::class,'category_id','id');
    }
    
    public function doctorSpecialty(){
        return $this->belongsTo(DoctorSpecialization::class,'specialization_primary_id','id');
    }
    
    public function doctorAddress(){
        return $this->hasOne(DoctorsAddress::class,'doctor_id','id');
    }

    public function doctorClinicAddress(){
        return $this->hasOne(DoctorsClinicAddress::class,'doctor_id','id');
    }
}
