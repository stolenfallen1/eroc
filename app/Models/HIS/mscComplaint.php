<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscComplaint extends Model
{
    use HasFactory;

    protected $table = "CDG_CORE.dbo.mscComplaints";

    protected $connection = "sqlsrv";

    protected $guarded = [];

}
