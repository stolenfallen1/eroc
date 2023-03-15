<?php

namespace App\Models\BuildFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classifications extends Model
{
    use HasFactory;
    protected $table = 'Itemsubcategoryclassification';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
