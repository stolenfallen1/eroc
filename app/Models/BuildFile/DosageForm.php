<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DosageForm extends Model
{
    use HasFactory;
    protected $table = 'mscDosageForms';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
