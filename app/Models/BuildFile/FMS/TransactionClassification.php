<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionClassification extends Model
{
    use HasFactory;
    protected $table = 'fmsTransactionClassification';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
