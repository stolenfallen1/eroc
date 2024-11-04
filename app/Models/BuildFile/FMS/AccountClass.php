<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\FMS\AccountType;
// use App\Models\BuildFile\FMS\AccountClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountClass extends Model
{
    use HasFactory;  
    protected $table = 'fmsAccountClass';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    public function getAccountClass(){
      return  $this->belongsTo(AccountClass::class,'Class','id');
    }

    public function getAccountType(){
       return $this->belongsTo(AccountType::class,'acct_type','id');
    }
}
