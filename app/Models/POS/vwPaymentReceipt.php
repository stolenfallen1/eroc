<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vwPaymentReceipt extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.vwPaymentReceipt';
    protected $guarded = [];
}
