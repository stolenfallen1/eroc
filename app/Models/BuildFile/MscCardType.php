<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscCardType extends Model
{
    use HasFactory;
    protected $table = 'CDG_CORE.dbo.mscBanks';
    protected $connection = "sqlsrv";
}
