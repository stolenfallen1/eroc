<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesType extends Model
{
    use HasFactory;
    protected $table = 'mscServiceType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
