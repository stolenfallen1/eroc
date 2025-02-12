<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class ParentRole
{

    public function staff()
    {
        return $this->parent_role('STAFF');
    }

    public function department_head()
    {
        return $this->parent_role('DEPARTMENT HEAD');
    }

    public function administrator()
    {
        return $this->parent_role('ADMINISTRATOR');
    }

    public function consultant()
    {
        return $this->parent_role('CONSULTANT');
    }

    public function purchaser()
    {
        return $this->parent_role('PURCHASER');
    }

    public function comptroller()
    {
        return $this->parent_role('COMPTROLLER');
    }

    public function corp_admin()
    {
        return $this->parent_role('CORPORATE ADMINISTRATOR');
    }

    public function president()
    {
        return $this->parent_role('PRESIDENT');
    }

    public function audit()
    {
        return $this->parent_role('AUDIT');
    }


    public function pharmacyCashier()
    {
        return $this->parent_role('PHARMACIST CASHIER');
    }

    public function pharmacyTakeOrder()
    {
        return $this->parent_role('PHARMACIST TAKE ORDER');
    }

    public function pharmacy_warehouse()
    {
        if (Auth::user()->warehouse_id == '78' || Auth::user()->warehouse_id == '66') return true;
    }
    public function isMedicine($itemgroup)
    {
        // Get the assigned item groups and convert to a collection
        $assignedItemGroup = collect(Auth::user()->assigneditemgroup);
        
        // Check if the itemgroup exists in the collection
        return $assignedItemGroup->contains($itemgroup);
    }

    public function pharmacyHead()
    {
        return $this->parent_role('PHARMACIST HEAD');
    }

    public function isdietary()
    {
        return Auth::user()->role->name === 'dietary';
    }

    public function isdietaryhead()
    {
        return Auth::user()->role->name === 'dietary head';
    }

    public function parent_role($role = null)
    {
        $parent = Auth::user()->approvaldetail ? Auth::user()->approvaldetail->approver_designation : Auth::user()->role->name;
        if (strtoupper($parent) === strtoupper($role)) {
            return true;
        } else {
            return false;
        }
    }
}
