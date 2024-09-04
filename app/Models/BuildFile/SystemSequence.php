<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSequence extends Model
{
    use HasFactory;
    protected $table = 'CDG_CORE.dbo.sysCentralSequences';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    
}
