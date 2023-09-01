<?php

namespace App\Models\Approver;

use App\Models\BuildFile\Branchs;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvApprover extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'invApprover';
    protected $guarded = [];

    
    public function branch(){
        return $this->belongsTo(Branchs::class, 'branch_id', 'id');
    }
    
    public function user_details(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function level(){
        return $this->belongsTo(InvApproverLevel::class, 'approver_id', 'id');
    }
}
