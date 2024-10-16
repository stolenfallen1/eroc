<?php

namespace App\Models\HIS;

use App\Models\HIS\PatientRegistry;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\BuildFile\FmsExamProcedureItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedsysCashAssessment extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_billing';
    protected $table = 'tbCashAssessment';
    protected $guarded = [];
    public $timestamps = false;
   
}
