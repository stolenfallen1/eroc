<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POSBIRSettings extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'possettings_bir';
    protected $guarded = [];
    
}
