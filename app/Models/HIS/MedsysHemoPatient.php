<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysHemoPatient extends Model
{
    use HasFactory;  
    protected $connection = 'sqlsrv_medsys_hemodialysis';
    protected $table = 'tbHemoMaster';
    protected $primaryKey = 'IDNum';
    protected $guarded = [];
    public $timestamps = false;
}
