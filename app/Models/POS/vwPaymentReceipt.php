<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use App\Models\POS\vwPaymentReceiptItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class vwPaymentReceipt extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.vwPaymentReceipt';
    protected $guarded = [];

    public function order_items(){
        return $this->hasMany(vwPaymentReceiptItems::class,'order_id', 'order_id');
    }
}
