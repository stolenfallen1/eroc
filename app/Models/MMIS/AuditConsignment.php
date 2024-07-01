<?php

namespace App\Models\MMIS;

use App\Models\MMIS\inventory\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditConsignment extends Model
{
    use HasFactory;
    protected $table = 'CDG_MMIS.dbo.auditConsignments';
    protected $connection = "sqlsrv_mmis";

    protected $guarded = [];

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'audit_by', 'idnumber');
    }
}
