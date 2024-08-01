<?php

namespace App\Models\HIS\his_functions;

use App\Models\BuildFile\Hospital\ShiftSchedules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashReceiptTerminal extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_billingOut';
    protected $table = 'CashReceiptTerminal';
    protected $guarded = [];
    public $timestamps = false;

    public function shift() {
        return $this->belongsTo(ShiftSchedules::class, 'shift_id', 'id');
    }
}
