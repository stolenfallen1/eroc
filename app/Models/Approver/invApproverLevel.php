<?php

namespace App\Models\Approver;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvApproverLevel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'invApproverLevel';
    protected $guarded = [];
}
