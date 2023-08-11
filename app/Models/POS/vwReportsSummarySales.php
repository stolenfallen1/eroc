<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vwReportsSummarySales extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos'; 
    protected $table = 'CDG_POS.dbo.vwSummary_Report';
    protected $guarded = [];
}
