<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\TerminalTakeOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Systerminals extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'systerminal';
    protected $guarded = [];

    public function takeOrders(){
        return $this->hasMany(TerminalTakeOrder::class, 'terminal_Id', 'id');
    }
}
