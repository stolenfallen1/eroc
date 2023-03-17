<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FmsTransactionCode extends Model
{
    use HasFactory;
    protected $table = 'fmsTransactionCodes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
