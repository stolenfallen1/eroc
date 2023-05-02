<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory; 
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.payments';
    protected $guarded = [];

}
