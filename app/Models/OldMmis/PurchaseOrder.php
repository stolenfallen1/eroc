<?php

namespace App\Models\OldMmis;

use App\Models\OldMMIS\Receiving;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'purchase_orders';
    protected $guarded = [];

    public function deliveries(){
        return $this->hasMany(Receiving::class, 'ponumber', 'ponumber');
    }
}
