<?php

namespace App\Models\HIS\medsys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbLABMaster extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_laboratory';
    protected $table = 'LABORATORY.dbo.tbLABMaster';
    protected $guarded = []; 
    public $timestamps = false;
}
