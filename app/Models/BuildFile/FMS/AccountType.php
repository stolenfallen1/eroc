<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;
    protected $table = 'fmsAccountType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
