<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fmsLedgerGroups extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'CDG_CORE.dbo.fmsLedgerGroups';
    protected $guarded = [];
    public $timestamps = false;
}
