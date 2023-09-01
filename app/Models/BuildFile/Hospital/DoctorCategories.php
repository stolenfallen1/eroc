<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorCategories extends Model
{
    use HasFactory;
    protected $table = 'mscDoctorCategory';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
