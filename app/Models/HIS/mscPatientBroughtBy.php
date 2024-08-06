<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscPatientBroughtBy extends Model
{
    use HasFactory;

    protected $table = 'mscPatientBroughtBy';

    protected $connection = 'sqlsrv';

    protected $guarded = [];

}
