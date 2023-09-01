<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitCards extends Model
{
    use HasFactory; 
    protected $table = 'mscDebitCards';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    public function bank(){
        return $this->belongsTo(Banks::class, 'bank_id', 'id');
    }
}
