<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccounts extends Model
{
    use HasFactory;
    protected $table = 'mscBankAccounts';
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function bank(){
        return $this->belongsTo(Banks::class, 'bank_id', 'id');
    }
}
