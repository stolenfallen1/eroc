<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpenningDetails extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CashOnHand_detail';
    protected $guarded = [];
    
}
