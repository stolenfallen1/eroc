<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Banks;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCards extends Model
{
    use HasFactory;
    protected $table = 'mscCreditCards';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function bank(){
        return $this->belongsTo(Banks::class, 'bank_id', 'id');
    }
}
