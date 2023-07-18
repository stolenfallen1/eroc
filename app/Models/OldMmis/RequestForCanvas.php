<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForCanvas extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'request_for_canvas';
    protected $guarded = [];

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'prnumber', 'prnumber');
    }
}
