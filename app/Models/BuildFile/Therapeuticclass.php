<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Therapeuticclass extends Model
{
    use HasFactory;
    
    protected $connection = "sqlsrv";
    protected $table = 'mscTherapeuticclass';
    protected $guarded = [];
}
