<?php

namespace App\Models\BuildFile\FMS;

use App\Models\HIS\fmsLedgerGroups;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\FMS\MedicareType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionCodes extends Model
{
    use HasFactory;
    protected $table = 'fmsTransactionCodes';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    public function medicare_type() {
        return $this->belongsTo(MedicareType::class, 'medicare_group_id', 'id');
    }
    public function ledger_groups() {
        return $this->belongsTo(fmsLedgerGroups::class, 'classification_code', 'lgrp');
    }
}
