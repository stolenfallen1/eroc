<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\VwPurchaseOrderDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwPurchaseOrderMaster extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwPurchaseOrderMaster';

    protected $appends = ['currency'];
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(VwPurchaseOrderDetails::class, 'po_id', 'id');
    }
    public function getCurrencyAttribute(){
        if($this->currency){
            $currency = $this->currency == 1 ? "₱" :"$";
            return $currency;
        }
    }
}
