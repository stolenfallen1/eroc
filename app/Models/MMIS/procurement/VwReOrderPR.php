<?php

namespace App\Models\MMIS\procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwReOrderPR extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.VwReOrderPR';

    protected $guarded = [];
}