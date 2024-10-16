<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscPaymentMethod extends Model
{
    use HasFactory;
    protected $table = 'mscPaymentMethods';
    protected $connection = "sqlsrv";
}
