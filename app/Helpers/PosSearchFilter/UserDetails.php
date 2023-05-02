<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserDetails
{

    public function userdetails($id=null)
    {
       return User::where('id',$id)->select('lastname','firstname','middlename','id','warehouse_id','branch_id')->first();
    }

}
