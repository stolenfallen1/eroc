<?php

namespace App\Models\MMIS\procurement;

use App\Models\User;
use App\Models\BuildFile\Vendors;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwForCanvasPR extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.VwForCanvasPurchaseRequest';
    protected $guarded = [];
    
    protected $appends = ['currency'];
    
    
    public function getCurrencyAttribute(){
        $currency = $this->currency_id == 1 ? "₱" :"$";
        return $currency;
    }
}
