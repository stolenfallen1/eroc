<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\FMS\AccountType;
use App\Models\BuildFile\FMS\AccountClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountGroup extends Model
{
    use HasFactory;
    protected $table = 'fmsAccountGroup';
    protected $connection = "sqlsrv";
    protected $guarded = [];


    public function getAccountClass(){
      return  $this->belongsTo(AccountClass::class,'account_class','id');
    }

    public function getAccountType(){
       return $this->belongsTo(AccountType::class,'account_type','id');
    }
}
