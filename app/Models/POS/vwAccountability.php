<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vwAccountability extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'vwAccountability_Report';
    protected $guarded = [];
}
