<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\DoctorCategories;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorsAddress extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'hmsDoctorsAddress';
    protected $guarded = [];
}
