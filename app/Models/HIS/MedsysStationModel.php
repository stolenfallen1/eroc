<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedsysStationModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_medsys_buildfile';
    protected $table = 'tbCoRoom';
    protected $guarded = [];
    public $timestamps = false;
}
