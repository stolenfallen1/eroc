<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequestDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendors extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'vendors';

    public function purchaseDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'vendor_id', 'id');
    }
}
