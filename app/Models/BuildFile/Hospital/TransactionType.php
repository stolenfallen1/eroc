<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalTransactionTypes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
