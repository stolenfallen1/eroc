<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    use HasFactory;

    protected $table = 'CDG_CORE.dbo.invItembrands';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
