<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllergyType extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscAllergyType';
    protected $guarded = [];
}
