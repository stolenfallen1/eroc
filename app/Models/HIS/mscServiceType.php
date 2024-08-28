<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscServiceType extends Model
{
    use HasFactory;
    protected $table = "CDG_CORE.dbo.mscServiceType";

    protected $connection = "sqlsrv";

    protected $guarded = [];
}
