<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HISChargeSequence extends Model
{
    use HasFactory;
    protected $table = 'CDG_DB.dbo.systemCentralSequences';
    protected $connection = "sqlsrv_medsys_patientdatacdg";
    protected $primaryKey = 'seq_prefix';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    
}
