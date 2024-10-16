<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysERMaster extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_medsys_patient_data';
    protected $table = 'tbERMaster';
    protected $primaryKey = 'IDnum';
    protected $guarded = [];
    public $timestamps = false;

    
}
