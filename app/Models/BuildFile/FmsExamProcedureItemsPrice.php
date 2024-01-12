<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FmsExamProcedureItemsPrice extends Model
{
    use HasFactory;
    protected $table = 'fmsExamProcedureItemPrices';
    protected $connection = "sqlsrv";
    protected $guarded = [];

}
