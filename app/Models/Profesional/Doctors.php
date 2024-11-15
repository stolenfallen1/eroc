<?php

namespace App\Models\Profesional;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctors extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'CDG_CORE.dbo.hmsDoctors';
    protected $guarded = [];
}
