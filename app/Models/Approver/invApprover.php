<?php

namespace App\Models\Approver;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvApprover extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'invApprover';
}
