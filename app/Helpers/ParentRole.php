<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class ParentRole{

    public function staff(){
        return $this->parent_role('STAFF');
    }
    
    public function department_head(){
        return $this->parent_role('DEPARTMENT HEAD');
    }

    public function administrator(){
        return $this->parent_role('ADMINISTRATOR');
    }

    public function consultant(){
        return $this->parent_role('CONSULTANT');
    }

    public function purchaser(){
        return $this->parent_role('PURCHASER');
    }

    public function comptroller(){
        return $this->parent_role('COMPTROLLER');
    }

    public function corp_admin(){
        return $this->parent_role('CORPORATE ADMINISTRATOR');
    }

    public function president(){
        return $this->parent_role('PRESIDENT');
    }

    public function audit(){
        return $this->parent_role('AUDIT');
    }


    public function pharmacyCashier(){
        return $this->parent_role('PHARMACIST CASHIER');
    }

    public function pharmacyTakeOrder(){
        return $this->parent_role('PHARMACIST TAKE ORDER');
    }

    public function pharmacyHead(){
        return $this->parent_role('PHARMACIST HEAD');
    }

    public function parent_role($role = null){
        $parent = Auth::user()->approvaldetail ? Auth::user()->approvaldetail->approver_designation : Auth::user()->role->name;
        if(strtoupper($parent) === strtoupper($role)){
            return true;
        }else{
            return false;
        }
    }
}