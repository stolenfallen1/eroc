<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscCreditCard extends Model
{   
    use HasFactory;
    protected $table = 'CDG_CORE.dbo.mscCreditCards';
    protected $connection = "sqlsrv";
}
