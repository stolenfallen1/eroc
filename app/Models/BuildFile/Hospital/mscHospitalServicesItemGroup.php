<?php

namespace App\Models\BuildFile\Hospital;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalServicesItemGroup extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscExamItemGroups';
    protected $guarded = [];
}
