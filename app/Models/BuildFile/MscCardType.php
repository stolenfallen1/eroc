<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscCardType extends Model
{
    use HasFactory;
    protected $table = 'mscBanks';
    protected $connection = "sqlsrv";
}
